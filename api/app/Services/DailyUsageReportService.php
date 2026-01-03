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
            ->select('utility_readings.*', 'daily_records.created_at as daily_record_created_at')
            ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
            ->where('daily_records.branch_id', $filters['branch_id'])
            ->whereDate('daily_records.created_at', '<', $filters['start_date'])
            ->orderBy('daily_records.created_at', 'desc')
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

        foreach ($waterReadings as $location => $readings) {
            $sortedReadings = $readings->sortByDesc(function ($reading) {
                if ($reading->getAttribute('daily_record_created_at')) {
                    return strtotime($reading->getAttribute('daily_record_created_at'));
                }
                if ($reading->dailyRecord && $reading->dailyRecord->created_at) {
                    return $reading->dailyRecord->created_at->timestamp;
                }
                return $reading->created_at ? $reading->created_at->timestamp : 0;
            });
            $waterReading = $sortedReadings->first();
            if ($waterReading && $waterReading->meter_value !== null) {
                $previousClosings['water'][$location] = round((float) $waterReading->meter_value, 2);
            }
        }

        // Electricity: ambil pembacaan terakhir per meter sebelum start_date
        $electricityReadings = ElectricityReading::query()
            ->select('electricity_readings.*', 'daily_records.created_at as daily_record_created_at')
            ->join('daily_records', 'electricity_readings.daily_record_id', '=', 'daily_records.id')
            ->where('daily_records.branch_id', $filters['branch_id'])
            ->whereDate('daily_records.created_at', '<', $filters['start_date'])
            ->orderBy('daily_records.created_at', 'desc')
            ->get()
            ->groupBy('electricity_meter_id');

        foreach ($electricityReadings as $meterId => $readings) {
            $latest = $readings->sortByDesc('daily_record_created_at')->first();

            if ($latest) {
                $previousClosings['electricity'][$meterId] = [
                    'wbp' => $latest->meter_value_wbp !== null ? round((float) $latest->meter_value_wbp, 2) : 0,
                    'lwbp' => $latest->meter_value_lwbp !== null ? round((float) $latest->meter_value_lwbp, 2) : 0,
                ];
            }
        }

        return $previousClosings;
    }

    /**
     * Process gas reading data for a daily record
     */
    public function processGasReading(Collection $gasReadings, array &$previousClosings): array
    {
        $gasReading = $gasReadings->first();
        $gasOpening = null;
        $gasClosing = null;
        $gasUsage = null;

        if ($gasReading) {
            $gasClosing = round($gasReading->meter_value, 2);
            $prevGas = $previousClosings['gas'] ?? null;
            $currentLocation = $gasReading->location ?? '';

            if (is_array($prevGas) && isset($prevGas['value']) && ($prevGas['location'] == $currentLocation)) {
                $gasOpening = $prevGas['value'];
            } else {
                $gasOpening = 0;
            }

            $gasUsage = round($gasClosing - $gasOpening, 2);

            // Update previous closing
            $previousClosings['gas'] = [
                'value' => $gasClosing,
                'location' => $currentLocation
            ];
        }

        return [
            'reading' => $gasReading,
            'opening' => $gasOpening,
            'closing' => $gasClosing,
            'usage' => $gasUsage,
        ];
    }

    /**
     * Process water readings data for a daily record
     */
    public function processWaterReadings(Collection $waterReadings, array &$previousClosings): array
    {
        $waterData = [];
        $waterReadingsSorted = $waterReadings->sortBy('location')->values();

        foreach ($waterReadingsSorted as $waterReading) {
            $waterClosing = round($waterReading->meter_value, 2);
            $location = $waterReading->location ?? 'default';

            $waterOpening = $previousClosings['water'][$location] ?? 0;
            $waterUsage = round($waterClosing - $waterOpening, 2);

            // Update previous closing
            $previousClosings['water'][$location] = $waterClosing;

            $waterData[] = [
                'location' => $waterReading->location,
                'opening' => $waterOpening,
                'closing' => $waterClosing,
                'usage' => $waterUsage,
                'photo' => $waterReading->photo ? asset('storage/' . $waterReading->photo) : null,
            ];
        }

        return $waterData;
    }

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

                $wbpClosing = $electricityReading->meter_value_wbp !== null ? round($electricityReading->meter_value_wbp, 2) : null;
                $lwbpClosing = $electricityReading->meter_value_lwbp !== null ? round($electricityReading->meter_value_lwbp, 2) : null;

                $wbpOpening = $previousClosings['electricity'][$meterId]['wbp'] ?? 0;
                $lwbpOpening = $previousClosings['electricity'][$meterId]['lwbp'] ?? 0;

                $wbpUsage = null;
                $lwbpUsage = null;
                $totalUsage = null;

                if ($wbpClosing !== null) {
                    $wbpUsage = round($wbpClosing - $wbpOpening, 2);
                }

                if ($lwbpClosing !== null) {
                    $lwbpUsage = round($lwbpClosing - $lwbpOpening, 2);
                }

                if ($wbpUsage !== null || $lwbpUsage !== null) {
                    $totalUsage = round(($wbpUsage ?? 0) + ($lwbpUsage ?? 0), 2);
                }

                $electricityData[] = [
                    'location' => $meter->location ?? '-',
                    'meter_name' => $meter->meter_name ?? null,
                    'meter_number' => $meter->meter_number ?? null,
                    'wbp_opening' => $wbpOpening,
                    'lwbp_opening' => $lwbpOpening,
                    'wbp_closing' => $wbpClosing,
                    'lwbp_closing' => $lwbpClosing,
                    'wbp_usage' => $wbpUsage,
                    'lwbp_usage' => $lwbpUsage,
                    'total_usage' => $totalUsage,
                    'meter_value' => null,
                    'photo' => null,
                    'photo_wbp' => $electricityReading->photo_wbp ? asset('storage/' . $electricityReading->photo_wbp) : null,
                    'photo_lwbp' => $electricityReading->photo_lwbp ? asset('storage/' . $electricityReading->photo_lwbp) : null,
                ];

                // Update previous closing
                $previousClosings['electricity'][$meterId] = [
                    'wbp' => $wbpClosing,
                    'lwbp' => $lwbpClosing,
                    'total' => null,
                ];
            }
        } else {
            // Fallback to legacy utility_readings electricity
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
            $wbpClosing = $electricityReading->meter_value_wbp ? round($electricityReading->meter_value_wbp, 2) : null;
            $lwbpClosing = $electricityReading->meter_value_lwbp ? round($electricityReading->meter_value_lwbp, 2) : null;
            $meterValue = $electricityReading->meter_value ? round($electricityReading->meter_value, 2) : null;

            $wbpOpening = $previousClosings['electricity'][$location]['wbp'] ?? 0;
            $lwbpOpening = $previousClosings['electricity'][$location]['lwbp'] ?? 0;

            $wbpUsage = null;
            $lwbpUsage = null;
            $totalUsage = null;

            if ($wbpClosing !== null) {
                $wbpUsage = round($wbpClosing - $wbpOpening, 2);
            }

            if ($lwbpClosing !== null) {
                $lwbpUsage = round($lwbpClosing - $lwbpOpening, 2);
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

            $previousClosings['electricity'][$location] = [
                'wbp' => $wbpClosing,
                'lwbp' => $lwbpClosing,
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
            'timestamp' => $dailyRecord->created_at->format('m/d/Y H:i:s'),
            'tanggal' => $dailyRecord->created_at->format('m/d/Y'),
            'nama' => $dailyRecord->user->name ?? '-',
            'outlet' => $dailyRecord->branch->name ?? '-',
            'total_customer' => $dailyRecord->total_customers ?? 0,
        ];

        if (!$category || $category === 'gas') {
            $gasReading = $gasData['reading'];
            $rowData['gas'] = [
                'stove_type' => $gasReading->stove_type ?? null,
                'gas_type' => $gasReading->gas_type ?? null,
                'location' => $gasReading->location ?? null,
                'opening' => $gasData['opening'],
                'closing' => $gasData['closing'],
                'usage' => $gasData['usage'],
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
