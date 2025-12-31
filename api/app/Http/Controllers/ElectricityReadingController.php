<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Interfaces\ElectricityReadingRepositoryInterface;
use App\Http\Requests\ElectricityReadingStoreRequest;
use App\Http\Requests\ElectricityReadingUpdateRequest;
use App\Http\Resources\ElectricityReadingResource;
use App\Http\Resources\PaginateResource;
use App\Models\ElectricityMeter;
use App\Models\ElectricityReading;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Support\Facades\Storage;

class ElectricityReadingController extends Controller implements HasMiddleware
{
    public function __construct(
        private ElectricityReadingRepositoryInterface $electricityReadingRepository,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('electricity-reading-list'), only: ['index', 'show', 'getAllPaginated', 'getMultiMeterReport']),
            new Middleware(PermissionMiddleware::using('electricity-reading-create'), only: ['store', 'storeMultiple']),
            new Middleware(PermissionMiddleware::using('electricity-reading-edit'), only: ['update']),
            new Middleware(PermissionMiddleware::using('electricity-reading-delete'), only: ['destroy']),
        ];
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ?string $dailyRecordId = null)
    {
        try {
            $filterDailyRecordId = $dailyRecordId ?? $request->daily_record_id;

            $electricityReadings = $this->electricityReadingRepository->getAll(
                $request->search,
                $filterDailyRecordId ? (int) $filterDailyRecordId : null,
                $request->electricity_meter_id ? (int) $request->electricity_meter_id : null
            );

            return ResponseHelper::jsonResponse(true, 'Data Pembacaan Listrik Berhasil Diambil', ElectricityReadingResource::collection($electricityReadings), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all paginated electricity readings.
     */
    public function getAllPaginated(Request $request)
    {
        try {
            $electricityReadings = $this->electricityReadingRepository->getAllPaginated(
                $request->per_page ?? 15,
                $request->search,
                $request->daily_record_id ? (int) $request->daily_record_id : null,
                $request->electricity_meter_id ? (int) $request->electricity_meter_id : null
            );

            return ResponseHelper::jsonResponse(true, 'Data Pembacaan Listrik Berhasil Diambil', PaginateResource::make($electricityReadings, ElectricityReadingResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ElectricityReadingStoreRequest $request, ?string $dailyRecordId = null)
    {
        try {
            $data = $request->validated();

            // Use dailyRecordId from route if provided
            if ($dailyRecordId) {
                $data['daily_record_id'] = $dailyRecordId;
            }

            // Handle photo uploads
            if ($request->hasFile('photo_wbp')) {
                $data['photo_wbp'] = $request->file('photo_wbp')->store('electricity-readings', 'public');
            }

            if ($request->hasFile('photo_lwbp')) {
                $data['photo_lwbp'] = $request->file('photo_lwbp')->store('electricity-readings', 'public');
            }

            $electricityReading = $this->electricityReadingRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Pembacaan Listrik Berhasil Dibuat', new ElectricityReadingResource($electricityReading->load(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store multiple electricity readings at once (for all meters in a branch).
     */
    public function storeMultiple(Request $request, string $dailyRecordId)
    {
        try {
            $request->validate([
                'readings' => 'required|array',
                'readings.*.id' => 'nullable|integer|exists:electricity_readings,id', // Optional: existing reading ID
                'readings.*.electricity_meter_id' => 'required|integer|exists:electricity_meters,id',
                'readings.*.meter_value_wbp' => 'required|numeric|min:0',
                'readings.*.meter_value_lwbp' => 'required|numeric|min:0',
                'readings.*.photo_wbp' => 'nullable|image|max:10240',
                'readings.*.photo_lwbp' => 'nullable|image|max:10240',
            ]);

            $createdReadings = [];

            foreach ($request->readings as $index => $readingData) {
                // Check if updating existing reading (by ID) or by daily_record + meter combination
                $existingReading = null;

                // First check if ID is provided (more reliable)
                if (!empty($readingData['id'])) {
                    $existingReading = ElectricityReading::find($readingData['id']);
                }

                // Fallback: check by daily_record_id + electricity_meter_id combination
                if (!$existingReading) {
                    $existingReading = ElectricityReading::where('daily_record_id', $dailyRecordId)
                        ->where('electricity_meter_id', $readingData['electricity_meter_id'])
                        ->first();
                }

                // Backend Validation for Photos: Required ONLY for new readings (no existing record)
                if (!$existingReading) {
                    if (!$request->hasFile("readings.$index.photo_wbp") || !$request->hasFile("readings.$index.photo_lwbp")) {
                        return ResponseHelper::jsonResponse(false, "Foto WBP dan LWBP wajib diupload untuk meter baru.", null, 422);
                    }
                }

                $data = [
                    'daily_record_id' => $dailyRecordId,
                    'electricity_meter_id' => $readingData['electricity_meter_id'],
                    'meter_value_wbp' => $readingData['meter_value_wbp'] ?? null,
                    'meter_value_lwbp' => $readingData['meter_value_lwbp'] ?? null,
                ];

                // Handle Photo Uploads
                if ($request->hasFile("readings.$index.photo_wbp")) {
                    $data['photo_wbp'] = $request->file("readings.$index.photo_wbp")->store('electricity-readings', 'public');
                    // If updating, delete old? (Optional optimization)
                    if ($existingReading && $existingReading->photo_wbp) {
                        Storage::disk('public')->delete($existingReading->photo_wbp);
                    }
                }

                if ($request->hasFile("readings.$index.photo_lwbp")) {
                    $data['photo_lwbp'] = $request->file("readings.$index.photo_lwbp")->store('electricity-readings', 'public');
                    if ($existingReading && $existingReading->photo_lwbp) {
                        Storage::disk('public')->delete($existingReading->photo_lwbp);
                    }
                }

                if ($existingReading) {
                    // Update existing
                    $existingReading->update($data);
                    $createdReadings[] = $existingReading->fresh(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter']);
                } else {
                    // Create new
                    $reading = $this->electricityReadingRepository->create($data);
                    $createdReadings[] = $reading->load(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter']);
                }
            }

            return ResponseHelper::jsonResponse(true, 'Pembacaan Listrik Berhasil Disimpan', ElectricityReadingResource::collection($createdReadings), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $electricityReading = $this->electricityReadingRepository->findById($id);

            return ResponseHelper::jsonResponse(true, 'Data Pembacaan Listrik Berhasil Diambil', new ElectricityReadingResource($electricityReading), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ElectricityReadingUpdateRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            // Handle photo uploads
            $existingReading = ElectricityReading::findOrFail($id);

            if ($request->hasFile('photo_wbp')) {
                // Delete old photo if exists
                if ($existingReading->photo_wbp) {
                    Storage::disk('public')->delete($existingReading->photo_wbp);
                }
                $data['photo_wbp'] = $request->file('photo_wbp')->store('electricity-readings', 'public');
            }

            if ($request->hasFile('photo_lwbp')) {
                // Delete old photo if exists
                if ($existingReading->photo_lwbp) {
                    Storage::disk('public')->delete($existingReading->photo_lwbp);
                }
                $data['photo_lwbp'] = $request->file('photo_lwbp')->store('electricity-readings', 'public');
            }

            $electricityReading = $this->electricityReadingRepository->update($id, $data);

            return ResponseHelper::jsonResponse(true, 'Pembacaan Listrik Berhasil Diubah', new ElectricityReadingResource($electricityReading), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Delete associated photos
            $reading = ElectricityReading::findOrFail($id);
            if ($reading->photo_wbp) {
                Storage::disk('public')->delete($reading->photo_wbp);
            }
            if ($reading->photo_lwbp) {
                Storage::disk('public')->delete($reading->photo_lwbp);
            }

            $this->electricityReadingRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Pembacaan Listrik Berhasil Dihapus', null, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get multi-meter electricity report with usage calculations.
     */
    public function getMultiMeterReport(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $branchId = $request->branch_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Get all meters for the branch
            $meters = ElectricityMeter::where('branch_id', $branchId)
                ->where('is_active', true)
                ->orderBy('meter_name')
                ->get();

            // Get all readings for context
            $readingsQuery = ElectricityReading::with(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])
                ->whereHas('dailyRecord', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->whereHas('electricityMeter', function ($q) {
                    $q->where('is_active', true);
                });

            if ($startDate) {
                $readingsQuery->whereHas('dailyRecord', function ($q) use ($startDate) {
                    $q->whereDate('created_at', '>=', $startDate);
                });
            }

            if ($endDate) {
                $readingsQuery->whereHas('dailyRecord', function ($q) use ($endDate) {
                    $q->whereDate('created_at', '<=', $endDate);
                });
            }

            $readings = $readingsQuery->get();

            // Group readings by date and meter
            $reportData = [];
            $previousClosings = [];

            // Sort readings by date
            $groupedByDate = $readings->groupBy(function ($reading) {
                return $reading->dailyRecord->created_at->format('Y-m-d');
            })->sortKeys();

            foreach ($groupedByDate as $date => $dateReadings) {
                $dateReadingsArray = [];

                foreach ($meters as $meter) {
                    $meterReading = $dateReadings->firstWhere('electricity_meter_id', $meter->id);

                    if ($meterReading) {
                        $wbpClosing = $meterReading->meter_value_wbp;
                        $lwbpClosing = $meterReading->meter_value_lwbp;

                        $wbpOpening = $previousClosings[$meter->id]['wbp'] ?? $wbpClosing;
                        $lwbpOpening = $previousClosings[$meter->id]['lwbp'] ?? $lwbpClosing;

                        $wbpUsage = $wbpClosing !== null && $wbpOpening !== null ? round($wbpClosing - $wbpOpening, 2) : null;
                        $lwbpUsage = $lwbpClosing !== null && $lwbpOpening !== null ? round($lwbpClosing - $lwbpOpening, 2) : null;
                        $totalUsage = ($wbpUsage !== null || $lwbpUsage !== null) ? round(($wbpUsage ?? 0) + ($lwbpUsage ?? 0), 2) : null;

                        $dateReadingsArray[] = [
                            'meter_id' => $meter->id,
                            'meter_name' => $meter->meter_name,
                            'meter_number' => $meter->meter_number,
                            'location' => $meter->location,
                            'power_capacity' => $meter->power_capacity,
                            'wbp_opening' => $wbpOpening,
                            'wbp_closing' => $wbpClosing,
                            'wbp_usage' => $wbpUsage,
                            'lwbp_opening' => $lwbpOpening,
                            'lwbp_closing' => $lwbpClosing,
                            'lwbp_usage' => $lwbpUsage,
                            'total_usage' => $totalUsage,
                            'photo_wbp' => $meterReading->photo_wbp ? asset('storage/' . $meterReading->photo_wbp) : null,
                            'photo_lwbp' => $meterReading->photo_lwbp ? asset('storage/' . $meterReading->photo_lwbp) : null,
                        ];

                        // Update previous closings
                        $previousClosings[$meter->id] = [
                            'wbp' => $wbpClosing,
                            'lwbp' => $lwbpClosing,
                        ];
                    }
                }

                if (!empty($dateReadingsArray)) {
                    $firstReading = $dateReadings->first();
                    $reportData[] = [
                        'date' => $date,
                        'formatted_date' => \Carbon\Carbon::parse($date)->format('d/m/Y'),
                        'user' => $firstReading->dailyRecord->user->name ?? '-',
                        'branch' => $firstReading->dailyRecord->branch->name ?? '-',
                        'readings' => $dateReadingsArray,
                    ];
                }
            }

            return ResponseHelper::jsonResponse(true, 'Laporan Multi-Meter Listrik Berhasil Diambil', [
                'meters' => $meters->map(function ($meter) {
                    return [
                        'id' => $meter->id,
                        'meter_name' => $meter->meter_name,
                        'meter_number' => $meter->meter_number,
                        'location' => $meter->location,
                        'power_capacity' => $meter->power_capacity,
                    ];
                }),
                'report' => $reportData,
            ], 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }
}
