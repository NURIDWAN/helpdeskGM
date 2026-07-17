<?php

namespace App\Services;

use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Models\ElectricityReading;
use App\Enums\UtilityCategory;
use Illuminate\Support\Collection;

class DailyUsageReportService
{
    /**
     * Initialize previous closing readings based on the last record before the selected start date.
     */
    public function initializePreviousClosings(array $filters): array
    {
        $previousClosings = [
            'gas' => null,
            'water' => [],
            'electricity' => [],
        ];

        if (empty($filters['branch_id']) || empty($filters['start_date'])) {
            return $previousClosings;
        }

        $baseQuery = UtilityReading::query()
            ->select('utility_readings.*', 'daily_records.date as daily_record_date', 'daily_records.created_at as daily_record_created_at')
            ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
            ->where('daily_records.branch_id', $filters['branch_id'])
            ->where(function ($q) use ($filters) {
                $q->whereDate('daily_records.date', '<', $filters['start_date'])
                  ->orWhere(function ($q2) use ($filters) {
                      $q2->whereNull('daily_records.date')
                          ->whereDate('daily_records.created_at', '<', $filters['start_date']);
                  });
            })
            ->orderByRaw('COALESCE(daily_records.date, daily_records.created_at) DESC')
            ->orderBy('utility_readings.id', 'desc');

        if (!empty($filters['user_id'])) {
            $baseQuery->where('daily_records.user_id', $filters['user_id']);
        }

        // Gas: ambil pembacaan terakhir sebelum start_date
        $gasReadingQuery = clone $baseQuery;
        $gasReading = $gasReadingQuery
            ->where('utility_readings.category', UtilityCategory::GAS->value)
            ->first();
        if ($gasReading && $gasReading->meter_value !== null) {
            $previousClosings['gas'] = [
                'value' => round((float) $gasReading->meter_value, 2),
                'location' => $gasReading->location ?? ''
            ];
        }

        // Water: ambil pembacaan terakhir per lokasi sebelum start_date
        $waterReadingsQuery = clone $baseQuery;
        $waterReadings = $waterReadingsQuery
            ->where('utility_readings.category', UtilityCategory::WATER->value)
            ->with('dailyRecord')
            ->get()
            ->groupBy(function ($reading) {
                return $reading->location ?? 'default';
            });

        // Check for single meter fallback
        if ($waterReadings->count() === 1) {
            $sortedReadings = $waterReadings->first()->sortByDesc(function ($reading) {
                if ($reading->getAttribute('daily_record_date')) {
                    return strtotime($reading->getAttribute('daily_record_date'));
                }
                if ($reading->getAttribute('daily_record_created_at')) {
                    return strtotime($reading->getAttribute('daily_record_created_at'));
                }
                if ($reading->dailyRecord && $reading->dailyRecord->date) {
                    return $reading->dailyRecord->date->timestamp;
                }
                return $reading->created_at ? $reading->created_at->timestamp : 0;
            });
            $waterReading = $sortedReadings->first();
             if ($waterReading && $waterReading->meter_value !== null) {
                $previousClosings['water']['_single_fallback'] = round((float) $waterReading->meter_value, 2);
            }
        }

        foreach ($waterReadings as $location => $readings) {
            $sortedReadings = $readings->sortByDesc(function ($reading) {
                if ($reading->getAttribute('daily_record_date')) {
                    return strtotime($reading->getAttribute('daily_record_date'));
                }
                if ($reading->getAttribute('daily_record_created_at')) {
                    return strtotime($reading->getAttribute('daily_record_created_at'));
                }
                if ($reading->dailyRecord && $reading->dailyRecord->date) {
                    return $reading->dailyRecord->date->timestamp;
                }
                return $reading->created_at ? $reading->created_at->timestamp : 0;
            });
            $waterReading = $sortedReadings->first();
            if ($waterReading && $waterReading->meter_value !== null) {
                // Normalize location key: lowercase and trim
                $normalizedLocation = trim(strtolower($location));
                $previousClosings['water'][$normalizedLocation] = round((float) $waterReading->meter_value, 2);
            }
        }

        // Electricity: ambil pembacaan terakhir per meter sebelum start_date
        $electricityReadings = ElectricityReading::query()
            ->select('electricity_readings.*', 'daily_records.date as daily_record_date', 'daily_records.created_at as daily_record_created_at')
            ->join('daily_records', 'electricity_readings.daily_record_id', '=', 'daily_records.id')
            ->where('daily_records.branch_id', $filters['branch_id'])
            ->where(function ($q) use ($filters) {
                $q->whereDate('daily_records.date', '<', $filters['start_date'])
                  ->orWhere(function ($q2) use ($filters) {
                      $q2->whereNull('daily_records.date')
                          ->whereDate('daily_records.created_at', '<', $filters['start_date']);
                  });
            })
            ->orderByRaw('COALESCE(daily_records.date, daily_records.created_at) DESC')
            ->get()
            ->groupBy('electricity_meter_id');

        foreach ($electricityReadings as $meterId => $readings) {
            $latest = $readings->sortByDesc(function ($r) {
                return $r->daily_record_date ?? $r->daily_record_created_at;
            })->first();

            if ($latest) {
                // Simplified: use singe meter_value
                $previousClosings['electricity'][$meterId] = $latest->meter_value !== null ? round((float) $latest->meter_value, 2) : 0;
            }
        }

        return $previousClosings;
    }

    // ... (skipped gas and water methods as they are fine) ...

    /**
     * Process electricity readings data for a daily record (multi-meter)
     */
    public function processElectricityReadings(Collection $multiMeterReadings, Collection $legacyReadings, array &$previousClosings): array
    {
        $electricityData = [];

        if ($multiMeterReadings->count() > 0) {
            $multiMeterSorted = $multiMeterReadings->sortBy(function ($reading) {
                return $reading->electricityMeter->location ?? $reading->electricityMeter->meter_name ?? 'default';
            })->values();

            foreach ($multiMeterSorted as $electricityReading) {
                $meter = $electricityReading->electricityMeter;
                $meterId = $electricityReading->electricity_meter_id;

                // Opening = meter_value yang diinput user hari ini
                $opening = $electricityReading->meter_value !== null ? round($electricityReading->meter_value, 2) : null;
                // Closing = Opening dari hari sebelumnya
                $closing = $previousClosings['electricity'][$meterId] ?? 0;

                $usage = null;

                if ($opening !== null) {
                    // Total Pemakaian = Opening - Closing
                    $usage = round($opening - $closing, 2);
                }

                $electricityData[] = [
                    'location' => $meter->location ?? '-',
                    'meter_name' => $meter->meter_name ?? null,
                    'meter_number' => $meter->meter_number ?? null,
                    'opening' => $opening,
                    'closing' => $closing,
                    'usage' => $usage,
                    'photo' => null,
                    'photo_path' => $electricityReading->photo ? asset('storage/' . $electricityReading->photo) : null,
                ];

                // Simpan Opening hari ini → jadi Closing hari berikutnya
                $previousClosings['electricity'][$meterId] = $opening;
            }
        } else {
            // Fallback to legacy utility_readings electricity if needed (or just empty)
            $electricityData = $this->processLegacyElectricityReadings($legacyReadings, $previousClosings);
        }

        return $electricityData;
    }

    /**
     * Process legacy electricity readings (from utility_readings table)
     */
    private function processLegacyElectricityReadings(Collection $legacyReadings, array &$previousClosings): array
    {
        $electricityData = [];
        $electricityReadingsSorted = $legacyReadings->sortBy('location')->values();

        foreach ($electricityReadingsSorted as $electricityReading) {
            $location = $electricityReading->location ?? 'default';
            // Opening = meter values input hari ini
            $wbpOpening = $electricityReading->meter_value_wbp ? round($electricityReading->meter_value_wbp, 2) : null;
            $lwbpOpening = $electricityReading->meter_value_lwbp ? round($electricityReading->meter_value_lwbp, 2) : null;
            $meterValue = $electricityReading->meter_value ? round($electricityReading->meter_value, 2) : null;

            // Closing = Opening dari hari sebelumnya
            $wbpClosing = $previousClosings['electricity'][$location]['wbp'] ?? 0;
            $lwbpClosing = $previousClosings['electricity'][$location]['lwbp'] ?? 0;

            $wbpUsage = null;
            $lwbpUsage = null;
            $totalUsage = null;

            // Total Pemakaian = Opening - Closing
            if ($wbpOpening !== null) {
                $wbpUsage = round($wbpOpening - $wbpClosing, 2);
            }

            if ($lwbpOpening !== null) {
                $lwbpUsage = round($lwbpOpening - $lwbpClosing, 2);
            }

            if ($wbpUsage !== null || $lwbpUsage !== null) {
                $totalUsage = round(($wbpUsage ?? 0) + ($lwbpUsage ?? 0), 2);
            }

            $electricityData[] = [
                'location' => $electricityReading->location,
                'wbp_opening' => $wbpOpening,
                'lwbp_opening' => $lwbpOpening,
                'wbp_closing' => $wbpClosing,
                'lwbp_closing' => $lwbpClosing,
                'wbp_usage' => $wbpUsage,
                'lwbp_usage' => $lwbpUsage,
                'total_usage' => $totalUsage,
                'meter_value' => $meterValue,
                'photo' => $electricityReading->photo ? asset('storage/' . $electricityReading->photo) : null,
                'photo_wbp' => $electricityReading->photo_wbp ? asset('storage/' . $electricityReading->photo_wbp) : null,
                'photo_lwbp' => $electricityReading->photo_lwbp ? asset('storage/' . $electricityReading->photo_lwbp) : null,
            ];

            // Simpan Opening hari ini → jadi Closing hari berikutnya
            $previousClosings['electricity'][$location] = [
                'wbp' => $wbpOpening,
                'lwbp' => $lwbpOpening,
                'total' => $meterValue,
            ];
        }

        return $electricityData;
    }

    /**
     * Build report row data for a daily record
     */
    public function buildReportRow(
        DailyRecord $dailyRecord,
        array $gasData,
        array $waterData,
        array $electricityData,
        ?string $category = null
    ): array {
        $rowData = [
            'timestamp' => $dailyRecord->date ? $dailyRecord->date->format('d/m/Y') : $dailyRecord->created_at->format('d/m/Y H:i:s'),
            'tanggal' => $dailyRecord->date ? $dailyRecord->date->format('d/m/Y') : $dailyRecord->created_at->format('d/m/Y'),
            'nama' => $dailyRecord->user->name ?? '-',
            'outlet' => $dailyRecord->branch->name ?? '-',
            'total_customer' => $dailyRecord->total_customers ?? 0,
        ];

        if (!$category || $category === 'gas') {
            $gasReading = $gasData['reading'] ?? null;
            $rowData['gas'] = [
                'stove_type' => $gasReading->stove_type ?? null,
                'gas_type' => $gasReading->gas_type ?? null,
                'location' => $gasReading->location ?? null,
                'opening' => $gasData['opening'] ?? null,
                'closing' => $gasData['closing'] ?? null,
                'usage' => $gasData['usage'] ?? null,
                'photo' => $gasReading && $gasReading->photo ? asset('storage/' . $gasReading->photo) : null,
                'photo_path' => $gasReading && $gasReading->photo ? $gasReading->photo : null,
            ];
        }

        if (!$category || $category === 'water') {
            $rowData['water'] = $waterData;
        }

        if (!$category || $category === 'electricity') {
            $rowData['electricity'] = $electricityData;
        }

        return $rowData;
    }
}
