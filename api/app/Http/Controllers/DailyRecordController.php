<?php

namespace App\Http\Controllers;

use App\Interfaces\DailyRecordRepositoryInterface;
use App\Http\Requests\DailyRecordStoreRequest;
use App\Http\Requests\DailyRecordUpdateRequest;
use App\Http\Resources\DailyRecordResource;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Enums\UtilityCategory;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Services\DailyUsageReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class DailyRecordController extends Controller implements HasMiddleware
{
    protected $dailyRecordRepository;
    protected $reportService;

    public function __construct(
        DailyRecordRepositoryInterface $dailyRecordRepository,
        DailyUsageReportService $reportService
    ) {
        $this->dailyRecordRepository = $dailyRecordRepository;
        $this->reportService = $reportService;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['daily-record-list|daily-record-create|daily-record-edit|daily-record-delete']), only: ['index', 'getAllPaginated', 'show', 'exportPdf', 'exportExcel']),
            new Middleware(PermissionMiddleware::using(['daily-record-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['daily-record-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['daily-record-delete']), only: ['destroy']),
        ];
    }

    /**
     * @OA\Get(
     *     path="/daily-records",
     *     tags={"Daily Records"},
     *     summary="Get all daily records",
     *     description="Get a list of all daily records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="branch_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/DailyRecord")
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $dailyRecords = $this->dailyRecordRepository->getAll(
                $request->search,
                $request->limit ?? $request->row_per_page,
                true,
                $request->user_id,
                $request->branch_id,
                $request->start_date,
                $request->end_date
            );

            return ResponseHelper::jsonResponse(true, 'Data Daily Record Berhasil Diambil', DailyRecordResource::collection($dailyRecords), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/daily-records/all/paginated",
     *     tags={"Daily Records"},
     *     summary="Get paginated daily records",
     *     description="Get a paginated list of daily records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="branch_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/PaginationMeta")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    /**
     * @OA\Get(
     *     path="/daily-records/all/paginated",
     *     tags={"Daily Records"},
     *     summary="Get paginated daily records",
     *     description="Get a paginated list of daily records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="branch_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/PaginationMeta")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function getAllPaginated(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'row_per_page' => 'required|integer',
            'user_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $dailyRecords = $this->dailyRecordRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page'],
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            return ResponseHelper::jsonResponse(true, 'Data Daily Record Berhasil Diambil', PaginateResource::make($dailyRecords, DailyRecordResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/daily-records",
     *     tags={"Daily Records"},
     *     summary="Create daily record",
     *     description="Create a new daily record",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"branch_id", "date", "total_passenger", "total_vehicle"},
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="total_passenger", type="integer"),
     *             @OA\Property(property="total_vehicle", type="integer"),
     *             @OA\Property(property="electricity_readings", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="utility_records", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Daily Record created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/DailyRecord")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function store(DailyRecordStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $dailyRecord = $this->dailyRecordRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Daily Record Berhasil Dibuat', new DailyRecordResource($dailyRecord), 201);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat membuat Daily Record', null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/daily-records/{id}",
     *     tags={"Daily Records"},
     *     summary="Get daily record by ID",
     *     description="Get a specific daily record by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/DailyRecord")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Daily Record not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $dailyRecord = $this->dailyRecordRepository->getById($id);

            // Load connected relations
            $dailyRecord->load(['electricityReadings.electricityMeter', 'utilityReadings']);

            // Fetch previous record for usage calculation
            $previousRecord = DailyRecord::where('branch_id', $dailyRecord->branch_id)
                ->where('created_at', '<', $dailyRecord->created_at)
                ->orderBy('created_at', 'desc')
                ->with(['electricityReadings', 'utilityReadings'])
                ->first();

            // Attach previous readings for calculation
            $dailyRecord->previous_readings = [
                'electricity' => $previousRecord ? $previousRecord->electricityReadings : [],
                'utility' => $previousRecord ? $previousRecord->utilityReadings : [],
                'record_date' => $previousRecord ? $previousRecord->created_at : null
            ];

            return ResponseHelper::jsonResponse(true, 'Data Daily Record Berhasil Diambil', new DailyRecordResource($dailyRecord), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Daily Record tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get previous readings for a branch to determine opening values for a new record.
     */
    /**
     * @OA\Get(
     *     path="/daily-records/previous-readings",
     *     tags={"Daily Records"},
     *     summary="Get previous readings",
     *     description="Get previous readings for a branch to determine opening values for a new record",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="branch_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Previous readings fetched",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function getPreviousReadings(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        try {
            $date = $request->date ? \Carbon\Carbon::parse($request->date) : now();

            $previousRecord = DailyRecord::where('branch_id', $request->branch_id)
                ->where('created_at', '<', $date)
                ->orderBy('created_at', 'desc')
                ->with(['electricityReadings', 'utilityReadings'])
                ->first();

            $previousReadings = [
                'electricity' => $previousRecord ? $previousRecord->electricityReadings : [],
                'utility' => $previousRecord ? $previousRecord->utilityReadings : [],
                'record_date' => $previousRecord ? $previousRecord->created_at : null
            ];

            return ResponseHelper::jsonResponse(true, 'Previous readings fetched', $previousReadings, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Error fetching previous readings', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/daily-records/{id}",
     *     tags={"Daily Records"},
     *     summary="Update daily record",
     *     description="Update an existing daily record",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="total_passenger", type="integer"),
     *             @OA\Property(property="total_vehicle", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daily Record updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/DailyRecord")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Daily Record not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(DailyRecordUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $dailyRecord = $this->dailyRecordRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Daily Record Berhasil Diubah', new DailyRecordResource($dailyRecord), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Daily Record tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat memperbarui Daily Record', null, 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/daily-records/{id}",
     *     tags={"Daily Records"},
     *     summary="Delete daily record",
     *     description="Delete a daily record",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Daily Record deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Daily Record not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $this->dailyRecordRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Daily Record Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Daily Record tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat menghapus Daily Record', null, 500);
        }
    }

    /**
     * Export daily records to PDF
     */
    public function exportPdf(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $dailyRecords = $this->dailyRecordRepository->getExportData(
                $request['search'] ?? null,
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $pdf = Pdf::loadView('daily-record.daily-record-pdf', [
                'dailyRecords' => $dailyRecords,
                'filters' => $request
            ]);

            $filename = 'catatan-harian-' . date('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Export daily records to Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $dailyRecords = $this->dailyRecordRepository->getExportData(
                $request['search'] ?? null,
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $filename = 'catatan-harian-' . date('Y-m-d-H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($dailyRecords) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Headers
                fputcsv($file, [
                    'ID',
                    'User',
                    'Cabang',
                    'Total Pelanggan',
                    'Tanggal Dibuat',
                    'Tanggal Diperbarui'
                ]);

                // Data
                foreach ($dailyRecords as $record) {
                    fputcsv($file, [
                        $record->id,
                        $record->user->name ?? '-',
                        $record->branch->name ?? '-',
                        $record->total_customers ?? '-',
                        $record->created_at->format('d/m/Y H:i'),
                        $record->updated_at->format('d/m/Y H:i')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat export Excel', null, 500);
        }
    }

    /**
     * Get daily usage report with opening/closing calculations
     */
    public function getDailyUsageReport(Request $request)
    {
        $request = $request->validate([
            'user_id' => 'nullable|integer',
            'branch_id' => 'required|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category' => 'nullable|in:gas,water,electricity' // Filter berdasarkan category
        ]);

        try {
            $dailyRecords = $this->dailyRecordRepository->getExportData(
                null,
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            // Load utility readings AND electricity readings (multi-meter) for all daily records
            $dailyRecords->load(['utilityReadings', 'electricityReadings.electricityMeter', 'user', 'branch']);

            // Sort by date ASC untuk perhitungan yang benar (seperti laporan arus kas)
            $dailyRecords = $dailyRecords->sortBy('created_at')->values();

            $reportData = [];

            // Track closing values dari record sebelumnya (untuk digunakan sebagai opening)
            $previousClosings = $this->initializePreviousClosings($request);

            foreach ($dailyRecords as $dailyRecord) {
                $utilityReadings = $dailyRecord->utilityReadings;

                // Group utility readings by category and location
                $gasReadings = $utilityReadings->where('category', UtilityCategory::GAS->value);
                $waterReadings = $utilityReadings->where('category', UtilityCategory::WATER->value);

                // Electricity: Check BOTH legacy utility_readings AND new electricity_readings (multi-meter)
                $legacyElectricityReadings = $utilityReadings->where('category', UtilityCategory::ELECTRICITY->value);
                $multiMeterReadings = $dailyRecord->electricityReadings ?? collect();

                // Get gas reading (usually one)
                $gasReading = $gasReadings->first();
                $gasOpening = null;
                $gasClosing = null;
                $gasUsage = null;

                if ($gasReading) {
                    $gasClosing = round($gasReading->meter_value, 2);
                    // Opening = closing dari record sebelumnya (jika ada) dan LOKASI SAMA
                    $prevGas = $previousClosings['gas'] ?? null;
                    $currentLocation = $gasReading->location ?? '';

                    if (is_array($prevGas) && isset($prevGas['value']) && ($prevGas['location'] == $currentLocation)) {
                        $gasOpening = $prevGas['value'];
                    } else {
                        $gasOpening = 0;
                    }

                    $gasUsage = round($gasClosing - $gasOpening, 2);

                    // Update previous closing untuk record berikutnya
                    $previousClosings['gas'] = [
                        'value' => $gasClosing,
                        'location' => $currentLocation
                    ];
                }

                // Get water readings
                // Sort by location untuk konsistensi urutan (sama seperti electricity)
                $waterReadingsSorted = $waterReadings->sortBy('location')->values();
                $waterData = [];
                foreach ($waterReadingsSorted as $waterReading) {
                    $waterClosing = round($waterReading->meter_value, 2);
                    $location = $waterReading->location ?? 'default';

                    // Opening = closing dari record sebelumnya dengan lokasi yang sama
                    // Jika tidak ada dengan lokasi yang sama, Opening harus 0 (karena meteran baru/beda lokasi)
                    $waterOpening = $previousClosings['water'][$location] ?? 0;
                    $waterUsage = round($waterClosing - $waterOpening, 2);

                    // Update previous closing untuk record berikutnya
                    $previousClosings['water'][$location] = $waterClosing;

                    $waterData[] = [
                        'location' => $waterReading->location,
                        'opening' => $waterOpening,
                        'closing' => $waterClosing,
                        'usage' => $waterUsage,
                        'photo' => $waterReading->photo ? asset('storage/' . $waterReading->photo) : null,
                    ];
                }

                // Get electricity readings (can be multiple) - Merge legacy and multi-meter
                // Prefer multi-meter readings, but include legacy ones if no multi-meter available
                $electricityData = [];

                // First, process multi-meter readings (these take priority)
                if ($multiMeterReadings->count() > 0) {
                    $multiMeterSorted = $multiMeterReadings->sortBy(function ($reading) {
                        return $reading->electricityMeter->location ?? $reading->electricityMeter->meter_name ?? 'default';
                    })->values();

                    foreach ($multiMeterSorted as $electricityReading) {
                        $meter = $electricityReading->electricityMeter;
                        $meterId = $electricityReading->electricity_meter_id;
                        $displayName = $meter->meter_name . ($meter->location ? ' (' . $meter->location . ')' : '');

                        $wbpClosing = $electricityReading->meter_value_wbp !== null ? round($electricityReading->meter_value_wbp, 2) : null;
                        $lwbpClosing = $electricityReading->meter_value_lwbp !== null ? round($electricityReading->meter_value_lwbp, 2) : null;

                        // Opening = closing dari record sebelumnya dengan electricity_meter_id yang sama
                        $wbpOpening = $previousClosings['electricity'][$meterId]['wbp'] ?? null;
                        $lwbpOpening = $previousClosings['electricity'][$meterId]['lwbp'] ?? null;

                        // Jika masih null, gunakan 0
                        $wbpOpening = $wbpOpening ?? 0;
                        $lwbpOpening = $lwbpOpening ?? 0;

                        // Calculate usage
                        $wbpUsage = null;
                        $lwbpUsage = null;
                        $totalUsage = null;

                        if ($wbpClosing !== null && $wbpOpening !== null) {
                            $wbpUsage = round($wbpClosing - $wbpOpening, 2);
                        }

                        if ($lwbpClosing !== null && $lwbpOpening !== null) {
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
                    $electricityReadingsSorted = $legacyElectricityReadings->sortBy('location')->values();

                    foreach ($electricityReadingsSorted as $electricityReading) {
                        $location = $electricityReading->location ?? 'default';
                        $wbpClosing = $electricityReading->meter_value_wbp ? round($electricityReading->meter_value_wbp, 2) : null;
                        $lwbpClosing = $electricityReading->meter_value_lwbp ? round($electricityReading->meter_value_lwbp, 2) : null;
                        $meterValue = $electricityReading->meter_value ? round($electricityReading->meter_value, 2) : null;

                        $wbpOpening = $previousClosings['electricity'][$location]['wbp'] ?? null;
                        $lwbpOpening = $previousClosings['electricity'][$location]['lwbp'] ?? null;

                        if ($wbpOpening === null && !empty($previousClosings['electricity'])) {
                            foreach ($previousClosings['electricity'] as $prevLocation => $prevData) {
                                if (isset($prevData['wbp']) && $prevData['wbp'] !== null) {
                                    $wbpOpening = $prevData['wbp'];
                                    break;
                                }
                            }
                        }
                        if ($lwbpOpening === null && !empty($previousClosings['electricity'])) {
                            foreach ($previousClosings['electricity'] as $prevLocation => $prevData) {
                                if (isset($prevData['lwbp']) && $prevData['lwbp'] !== null) {
                                    $lwbpOpening = $prevData['lwbp'];
                                    break;
                                }
                            }
                        }

                        $wbpOpening = $wbpOpening ?? 0;
                        $lwbpOpening = $lwbpOpening ?? 0;

                        $wbpUsage = null;
                        $lwbpUsage = null;
                        $totalUsage = null;

                        if ($wbpClosing !== null && $wbpOpening !== null) {
                            $wbpUsage = round($wbpClosing - $wbpOpening, 2);
                        }

                        if ($lwbpClosing !== null && $lwbpOpening !== null) {
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
                }

                // Filter berdasarkan category jika dipilih
                $category = $request['category'] ?? null;
                if ($category && !in_array($category, ['gas', 'water', 'electricity'])) {
                    continue; // Skip jika category tidak valid
                }

                $rowData = [
                    'timestamp' => $dailyRecord->created_at->format('m/d/Y H:i:s'),
                    'tanggal' => $dailyRecord->created_at->format('m/d/Y'),
                    'nama' => $dailyRecord->user->name ?? '-',
                    'outlet' => $dailyRecord->branch->name ?? '-',
                    'total_customer' => $dailyRecord->total_customers ?? 0,
                ];

                // Hanya tambahkan data category yang dipilih, atau semua jika tidak ada filter
                if (!$category || $category === 'gas') {
                    $rowData['gas'] = [
                        'stove_type' => $gasReading->stove_type ?? null,
                        'gas_type' => $gasReading->gas_type ?? null,
                        'location' => $gasReading->location ?? null,
                        'opening' => $gasOpening,
                        'closing' => $gasClosing,
                        'usage' => $gasUsage,
                        'photo' => $gasReading && $gasReading->photo ? asset('storage/' . $gasReading->photo) : null,
                        'photo_path' => $gasReading && $gasReading->photo ? $gasReading->photo : null, // Path untuk PDF
                    ];
                }
                if (!$category || $category === 'water') {
                    $rowData['water'] = $waterData;
                    // Tambahkan photo_path untuk setiap water entry
                    foreach ($rowData['water'] as &$water) {
                        if ($water['photo']) {
                            $waterReading = $waterReadings->firstWhere('location', $water['location']);
                            $water['photo_path'] = $waterReading && $waterReading->photo ? $waterReading->photo : null;
                        }
                    }
                }
                if (!$category || $category === 'electricity') {
                    $rowData['electricity'] = $electricityData; // Return all electricity data
                    // Photo paths are already included in electricityData
                    // Add photo_path fields for consistency (used by PDF export)
                    foreach ($rowData['electricity'] as &$elec) {
                        // For multi-meter, photo_wbp and photo_lwbp are already set
                        // Just add the path versions by extracting from the URL or from source data
                        if ($multiMeterReadings->count() > 0) {
                            // Multi-meter: Find matching reading by location/meter_name
                            $matchingReading = $multiMeterReadings->first(function ($r) use ($elec) {
                                $meterLocation = $r->electricityMeter->location ?? $r->electricityMeter->meter_name ?? 'default';
                                return $meterLocation === $elec['location'];
                            });
                            $elec['photo_path'] = null;
                            $elec['photo_wbp_path'] = $matchingReading && $matchingReading->photo_wbp ? $matchingReading->photo_wbp : null;
                            $elec['photo_lwbp_path'] = $matchingReading && $matchingReading->photo_lwbp ? $matchingReading->photo_lwbp : null;
                        } else {
                            // Legacy: Find from legacyElectricityReadings
                            $elecReading = $legacyElectricityReadings->firstWhere('location', $elec['location']);
                            $elec['photo_path'] = $elecReading && $elecReading->photo ? $elecReading->photo : null;
                            $elec['photo_wbp_path'] = $elecReading && $elecReading->photo_wbp ? $elecReading->photo_wbp : null;
                            $elec['photo_lwbp_path'] = $elecReading && $elecReading->photo_lwbp ? $elecReading->photo_lwbp : null;
                        }
                    }
                }

                $reportData[] = $rowData;
            }

            return ResponseHelper::jsonResponse(true, 'Laporan Daily Usage Berhasil Diambil', $reportData, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export daily usage report to Excel (.xlsx)
     */
    public function exportDailyUsageReport(Request $request)
    {
        $request = $request->validate([
            'user_id' => 'nullable|integer',
            'branch_id' => 'required|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category' => 'nullable|string'
        ]);

        try {
            $dailyRecords = $this->dailyRecordRepository->getExportData(
                null,
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $dailyRecords->load(['utilityReadings', 'electricityReadings.electricityMeter', 'user', 'branch']);
            $dailyRecords = $dailyRecords->sortBy('created_at')->values();

            $branch = \App\Models\Branch::find($request['branch_id']);
            $initialPreviousClosings = $this->initializePreviousClosings($request);

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan Daily Usage');

            // Base URL for photos
            $baseUrl = config('app.url') . '/storage/';

            // Helper function to add photo hyperlink to cell
            $addPhotoHyperlink = function ($sheet, $photoPath, $cell, $title = 'Lihat Foto') use ($baseUrl) {
                if (empty($photoPath)) {
                    $sheet->setCellValue($cell, '-');
                    return;
                }

                $photoUrl = $baseUrl . $photoPath;

                // Set cell value with link text
                $sheet->setCellValue($cell, $title);

                // Add hyperlink
                $sheet->getCell($cell)->getHyperlink()->setUrl($photoUrl);

                // Style as hyperlink (blue, underline)
                $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0066CC'));
                $sheet->getStyle($cell)->getFont()->setUnderline(true);
            };

            // Define column letters
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE'];

            // Row 1: Title
            $sheet->setCellValue('A1', 'LAPORAN DAILY USAGE - SEMUA KATEGORI');
            $sheet->mergeCells('A1:AC1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2C3E50'); // Dark blue-gray
            $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');

            // Row 2: Branch info
            $sheet->setCellValue('A2', 'Cabang: ' . ($branch->name ?? '-'));
            $sheet->mergeCells('A2:AC2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF34495E');
            $sheet->getStyle('A2')->getFont()->getColor()->setARGB('FFFFFFFF');

            // Row 3: Date range info (if filters applied)
            $sheet->setCellValue('A3', 'Periode: ' . ($request['start_date'] ?? 'Semua') . ' s/d ' . ($request['end_date'] ?? 'Semua'));
            $sheet->mergeCells('A3:AC3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A3')->getFont()->setItalic(true);

            // ========== ROW 4 & 5: HEADERS ==========

            // Common Headers (A-F): Merge vertically across rows 4-5
            $commonHeaders = ['NO', 'TIMESTAMP', 'TANGGAL', 'NAMA', 'OUTLET', 'TOTAL CUSTOMER'];
            $commonColor = 'FF3498DB'; // Nice blue color

            for ($i = 0; $i < count($commonHeaders); $i++) {
                $col = $columns[$i];
                $sheet->setCellValue($col . '4', $commonHeaders[$i]);
                $sheet->mergeCells($col . '4:' . $col . '5'); // Merge row 4 and 5
                $sheet->getStyle($col . '4:' . $col . '5')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle($col . '4:' . $col . '5')->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle($col . '4:' . $col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($commonColor);
                $sheet->getStyle($col . '4:' . $col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '4:' . $col . '5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($col . '4:' . $col . '5')->getAlignment()->setWrapText(true);
            }

            // LAPORAN GAS Header (Row 4, G-M)
            $sheet->setCellValue('G4', 'LAPORAN GAS');
            $sheet->mergeCells('G4:M4');
            $sheet->getStyle('G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE67E22'); // Orange
            $sheet->getStyle('G4')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Gas sub-headers (Row 5, G-M)
            $gasHeaders = ['Jenis Kompor', 'Jenis Gas', 'Opening', 'Closing', 'Total Pemakaian', 'Foto', 'Lokasi'];
            for ($i = 0; $i < count($gasHeaders); $i++) {
                $col = $columns[6 + $i]; // Start from G (index 6)
                $sheet->setCellValue($col . '5', $gasHeaders[$i]);
                $sheet->getStyle($col . '5')->getFont()->setBold(true);
                $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF39C12'); // Light orange
                $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '5')->getAlignment()->setWrapText(true);
            }

            // LAPORAN AIR Header (Row 4, N-R)
            $sheet->setCellValue('N4', 'LAPORAN AIR');
            $sheet->mergeCells('N4:R4');
            $sheet->getStyle('N4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3498DB'); // Blue
            $sheet->getStyle('N4')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Water sub-headers (Row 5, N-R)
            $waterHeaders = ['Opening', 'Closing', 'Total Pemakaian', 'Foto', 'Lokasi'];
            for ($i = 0; $i < count($waterHeaders); $i++) {
                $col = $columns[13 + $i]; // Start from N (index 13)
                $sheet->setCellValue($col . '5', $waterHeaders[$i]);
                $sheet->getStyle($col . '5')->getFont()->setBold(true);
                $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF5DADE2'); // Light blue
                $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '5')->getAlignment()->setWrapText(true);
            }

            // LAPORAN LISTRIK Header (Row 4, S-AB)
            $sheet->setCellValue('S4', 'LAPORAN LISTRIK');
            $sheet->mergeCells('S4:AC4');
            $sheet->getStyle('S4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF27AE60'); // Green
            $sheet->getStyle('S4')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('S4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Electricity sub-headers (Row 5, S-AC) - Added Nama column
            $elecHeaders = ['Nama', 'Lokasi', 'WBP Opening', 'LWBP Opening', 'WBP Closing', 'LWBP Closing', 'Pemakaian WBP', 'Pemakaian LWBP', 'Total Pemakaian', 'Foto WBP', 'Foto LWBP'];
            for ($i = 0; $i < count($elecHeaders); $i++) {
                $col = $columns[18 + $i]; // Start from S (index 18)
                $sheet->setCellValue($col . '5', $elecHeaders[$i]);
                $sheet->getStyle($col . '5')->getFont()->setBold(true);
                $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF58D68D'); // Light green
                $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '5')->getAlignment()->setWrapText(true);
            }

            // Add borders to all header cells
            $sheet->getStyle('A4:AC5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Set row height for better readability
            $sheet->getRowDimension(4)->setRowHeight(25);
            $sheet->getRowDimension(5)->setRowHeight(30);

            // Track closing values
            $previousClosings = $initialPreviousClosings;
            $currentRow = 6;
            $rowNumber = 1; // Row counter for NO column

            foreach ($dailyRecords as $dailyRecord) {
                $utilityReadings = $dailyRecord->utilityReadings;
                $gasReadings = $utilityReadings->where('category', UtilityCategory::GAS->value);
                $waterReadings = $utilityReadings->where('category', UtilityCategory::WATER->value)->sortBy('location')->values();
                $electricityReadings = $dailyRecord->electricityReadings->sortBy(function ($r) {
                    return $r->electricityMeter->meter_name ?? '';
                })->values();

                $maxRows = max(1, $electricityReadings->count());
                $startRow = $currentRow;

                $totalWbpUsage = 0;
                $totalLwbpUsage = 0;
                $totalElecUsage = 0;

                for ($i = 0; $i < $maxRows; $i++) {
                    $col = 0;

                    // Common Data (only first row)
                    if ($i === 0) {
                        $sheet->setCellValue($columns[$col++] . $currentRow, $rowNumber); // NO column
                        $sheet->setCellValue($columns[$col++] . $currentRow, $dailyRecord->created_at->format('m/d/Y H:i:s'));
                        $sheet->setCellValue($columns[$col++] . $currentRow, $dailyRecord->created_at->format('m/d/Y'));
                        $sheet->setCellValue($columns[$col++] . $currentRow, $dailyRecord->user->name ?? '-');
                        $sheet->setCellValue($columns[$col++] . $currentRow, $dailyRecord->branch->name ?? '-');
                        $sheet->setCellValue($columns[$col++] . $currentRow, $dailyRecord->total_customers ?? 0);
                    } else {
                        $col = 6; // Shifted by 1 for NO column
                    }

                    // Gas Data (only first row)
                    if ($i === 0) {
                        $gasReading = $gasReadings->first();
                        if ($gasReading) {
                            $gasClosing = round($gasReading->meter_value, 2);
                            $prevGas = $previousClosings['gas'] ?? null;
                            $currentLocation = $gasReading->location ?? '';

                            $gasOpening = (is_array($prevGas) && isset($prevGas['value']) && ($prevGas['location'] == $currentLocation))
                                ? $prevGas['value'] : 0;
                            $gasUsage = round($gasClosing - $gasOpening, 2);

                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasReading->stove_type ?? '-');
                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasReading->gas_type ?? '-');
                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasOpening);
                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasClosing);
                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasUsage);
                            // Gas photo hyperlink
                            $gasPhotoCell = $columns[$col] . $currentRow;
                            $addPhotoHyperlink($sheet, $gasReading->photo, $gasPhotoCell, 'Foto Gas');
                            $col++;
                            $sheet->setCellValue($columns[$col++] . $currentRow, $gasReading->location ?? '');

                            $previousClosings['gas'] = ['value' => $gasClosing, 'location' => $currentLocation];
                        } else {
                            for ($j = 0; $j < 7; $j++)
                                $sheet->setCellValue($columns[$col++] . $currentRow, '-');
                        }
                    } else {
                        $col = 13; // Shifted by 1 for NO column
                    }

                    // Water Data (only first row)
                    if ($i === 0) {
                        $waterReading = $waterReadings->first();
                        if ($waterReading) {
                            $waterClosing = round($waterReading->meter_value, 2);
                            $location = $waterReading->location ?? 'default';
                            $waterOpening = $previousClosings['water'][$location] ?? 0;
                            $waterUsage = round($waterClosing - $waterOpening, 2);

                            $sheet->setCellValue($columns[$col++] . $currentRow, $waterOpening);
                            $sheet->setCellValue($columns[$col++] . $currentRow, $waterClosing);
                            $sheet->setCellValue($columns[$col++] . $currentRow, $waterUsage);
                            // Water photo hyperlink
                            $waterPhotoCell = $columns[$col] . $currentRow;
                            $addPhotoHyperlink($sheet, $waterReading->photo, $waterPhotoCell, 'Foto Air');
                            $col++;
                            $sheet->setCellValue($columns[$col++] . $currentRow, $waterReading->location ?? '');

                            $previousClosings['water'][$location] = $waterClosing;
                        } else {
                            for ($j = 0; $j < 5; $j++)
                                $sheet->setCellValue($columns[$col++] . $currentRow, '-');
                        }
                    } else {
                        $col = 18; // Shifted by 1 for NO column
                    }

                    // Electricity Data (each row = one meter)
                    if (isset($electricityReadings[$i])) {
                        $elec = $electricityReadings[$i];
                        $meter = $elec->electricityMeter;
                        $meterId = $elec->electricity_meter_id;
                        $displayName = $meter->meter_name . ($meter->location ? ' (' . $meter->location . ')' : '');

                        $wbpClosing = $elec->meter_value_wbp !== null ? round($elec->meter_value_wbp, 2) : '';
                        $lwbpClosing = $elec->meter_value_lwbp !== null ? round($elec->meter_value_lwbp, 2) : '';

                        $wbpOpening = $previousClosings['electricity'][$meterId]['wbp'] ?? ($wbpClosing !== '' ? 0 : '');
                        $lwbpOpening = $previousClosings['electricity'][$meterId]['lwbp'] ?? ($lwbpClosing !== '' ? 0 : '');

                        $wbpUsage = ($wbpClosing !== '' && $wbpOpening !== '') ? round($wbpClosing - $wbpOpening, 2) : '';
                        $lwbpUsage = ($lwbpClosing !== '' && $lwbpOpening !== '') ? round($lwbpClosing - $lwbpOpening, 2) : '';
                        $elecTotal = ($wbpUsage !== '' || $lwbpUsage !== '')
                            ? round((is_numeric($wbpUsage) ? $wbpUsage : 0) + (is_numeric($lwbpUsage) ? $lwbpUsage : 0), 2) : '';
                        if (is_numeric($wbpUsage))
                            $totalWbpUsage += $wbpUsage;
                        if (is_numeric($lwbpUsage))
                            $totalLwbpUsage += $lwbpUsage;
                        if (is_numeric($elecTotal))
                            $totalElecUsage += $elecTotal;

                        $sheet->setCellValue($columns[$col++] . $currentRow, $meter->meter_name ?? 'Meter ' . ($i + 1));
                        $sheet->setCellValue($columns[$col++] . $currentRow, $meter->location ?? '-');
                        $sheet->setCellValue($columns[$col++] . $currentRow, $wbpOpening);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $lwbpOpening);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $wbpClosing);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $lwbpClosing);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $wbpUsage);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $lwbpUsage);
                        $sheet->setCellValue($columns[$col++] . $currentRow, $elecTotal);
                        // Electricity WBP photo hyperlink
                        $elecWbpPhotoCell = $columns[$col] . $currentRow;
                        $addPhotoHyperlink($sheet, $elec->photo_wbp, $elecWbpPhotoCell, 'Foto WBP');
                        $col++;
                        // Electricity LWBP photo hyperlink
                        $elecLwbpPhotoCell = $columns[$col] . $currentRow;
                        $addPhotoHyperlink($sheet, $elec->photo_lwbp, $elecLwbpPhotoCell, 'Foto LWBP');
                        $col++;

                        $previousClosings['electricity'][$meterId] = ['wbp' => $wbpClosing, 'lwbp' => $lwbpClosing];
                    } else {
                        for ($j = 0; $j < 11; $j++)
                            $sheet->setCellValue($columns[$col++] . $currentRow, '');
                    }

                    // Add borders to data row
                    $sheet->getStyle('A' . $currentRow . ':AC' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $currentRow++;
                }

                // TOTAL row for electricity (if more than 1 meter)
                if ($electricityReadings->count() > 1) {
                    $sheet->setCellValue('S' . $currentRow, 'TOTAL');
                    $sheet->mergeCells('S' . $currentRow . ':T' . $currentRow); // Merge Nama and Lokasi for TOTAL
                    $sheet->setCellValue('U' . $currentRow, '-');
                    $sheet->setCellValue('V' . $currentRow, '-');
                    $sheet->setCellValue('W' . $currentRow, '-');
                    $sheet->setCellValue('X' . $currentRow, '-');
                    $sheet->setCellValue('Y' . $currentRow, $totalWbpUsage);
                    $sheet->setCellValue('Z' . $currentRow, $totalLwbpUsage);
                    $sheet->setCellValue('AA' . $currentRow, $totalElecUsage);

                    // Style TOTAL row
                    $sheet->getStyle('S' . $currentRow . ':AC' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00'); // Yellow
                    $sheet->getStyle('S' . $currentRow . ':AC' . $currentRow)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $currentRow . ':AC' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $currentRow++;
                }

                // Merge common cells if multiple rows
                if ($maxRows > 1) {
                    for ($c = 0; $c < 6; $c++) { // A-F (NO + 5 common columns)
                        $sheet->mergeCells($columns[$c] . $startRow . ':' . $columns[$c] . ($startRow + $maxRows - 1));
                    }
                    for ($c = 6; $c < 13; $c++) { // G-M (Gas columns)
                        $sheet->mergeCells($columns[$c] . $startRow . ':' . $columns[$c] . ($startRow + $maxRows - 1));
                    }
                    for ($c = 13; $c < 18; $c++) { // N-R (Water columns)
                        $sheet->mergeCells($columns[$c] . $startRow . ':' . $columns[$c] . ($startRow + $maxRows - 1));
                    }
                }

                $rowNumber++; // Increment row number for next daily record
            }

            // Auto-size columns
            foreach (range('A', 'Z') as $colLetter) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            $sheet->getColumnDimension('AA')->setAutoSize(true);
            $sheet->getColumnDimension('AB')->setAutoSize(true);
            $sheet->getColumnDimension('AC')->setAutoSize(true);

            // Set vertical alignment
            $sheet->getStyle('A1:AC' . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Create file and return response
            $filename = 'laporan-daily-usage-' . date('Y-m-d-H-i-s') . '.xlsx';

            // Create temp file with proper xlsx extension
            $tempFile = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Verify file was created and has content
            if (!file_exists($tempFile) || filesize($tempFile) < 1000) {
                throw new \Exception('Excel file creation failed');
            }

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat export: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export daily usage report to PDF with images
     */
    public function exportDailyUsageReportPdf(Request $request)
    {
        $request = $request->validate([
            'user_id' => 'nullable|integer',
            'branch_id' => 'required|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category' => 'nullable|string' // Category optional (defaults to all)
        ]);

        try {
            // Reuse logic from getDailyUsageReport
            $dailyRecords = $this->dailyRecordRepository->getExportData(
                null,
                $request['user_id'] ?? null,
                $request['branch_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $dailyRecords->load(['utilityReadings', 'electricityReadings.electricityMeter', 'user', 'branch']);
            $dailyRecords = $dailyRecords->sortBy('created_at')->values();

            $reportData = [];
            $previousClosings = $this->initializePreviousClosings($request);

            $category = $request['category'] ?: 'all';

            foreach ($dailyRecords as $dailyRecord) {
                $utilityReadings = $dailyRecord->utilityReadings;
                $gasReadings = $utilityReadings->where('category', UtilityCategory::GAS->value);
                $waterReadings = $utilityReadings->where('category', UtilityCategory::WATER->value);
                $electricityReadings = $utilityReadings->where('category', UtilityCategory::ELECTRICITY->value);

                $rowData = [
                    'timestamp' => $dailyRecord->created_at->format('m/d/Y H:i:s'),
                    'tanggal' => $dailyRecord->created_at->format('m/d/Y'),
                    'nama' => $dailyRecord->user->name ?? '-',
                    'outlet' => $dailyRecord->branch->name ?? '-',
                    'total_customer' => $dailyRecord->total_customers ?? 0,
                ];

                // Process gas
                if ($category === 'gas' || $category === 'all') {
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
                        $previousClosings['gas'] = [
                            'value' => $gasClosing,
                            'location' => $currentLocation
                        ];
                    }

                    $rowData['gas'] = [
                        'stove_type' => $gasReading->stove_type ?? null,
                        'gas_type' => $gasReading->gas_type ?? null,
                        'location' => $gasReading->location ?? null,
                        'opening' => $gasOpening,
                        'closing' => $gasClosing,
                        'usage' => $gasUsage,
                        'photo_path' => $gasReading && $gasReading->photo ? $gasReading->photo : null,
                    ];
                }

                // Process water
                if ($category === 'water' || $category === 'all') {
                    $waterData = [];
                    foreach ($waterReadings as $waterReading) {
                        $waterClosing = round($waterReading->meter_value, 2);
                        $location = $waterReading->location ?? 'default';
                        // Opening = closing dari record sebelumnya dengan lokasi yang sama
                        $waterOpening = $previousClosings['water'][$location] ?? 0;
                        $waterUsage = round($waterClosing - $waterOpening, 2);
                        $previousClosings['water'][$location] = $waterClosing;

                        $waterData[] = [
                            'location' => $waterReading->location,
                            'opening' => $waterOpening,
                            'closing' => $waterClosing,
                            'usage' => $waterUsage,
                            'photo_path' => $waterReading->photo ? $waterReading->photo : null,
                        ];
                    }
                    $rowData['water'] = $waterData;
                }

                // Process electricity
                if ($category === 'electricity' || $category === 'all') {
                    $electricityReadingsSorted = $dailyRecord->electricityReadings->sortBy(function ($r) {
                        return $r->electricityMeter->meter_name ?? '';
                    })->values();
                    $electricityData = [];

                    foreach ($electricityReadingsSorted as $electricityReading) {
                        $meter = $electricityReading->electricityMeter;
                        $meterId = $electricityReading->electricity_meter_id;
                        $displayName = $meter->meter_name . ($meter->location ? ' (' . $meter->location . ')' : '');

                        $wbpClosing = $electricityReading->meter_value_wbp !== null ? round($electricityReading->meter_value_wbp, 2) : null;
                        $lwbpClosing = $electricityReading->meter_value_lwbp !== null ? round($electricityReading->meter_value_lwbp, 2) : null;

                        // Opening = closing dari record sebelumnya dengan meter_id yang sama
                        $wbpOpening = $previousClosings['electricity'][$meterId]['wbp'] ?? null;
                        $lwbpOpening = $previousClosings['electricity'][$meterId]['lwbp'] ?? null;

                        // Jika masih null, gunakan 0
                        $wbpOpening = $wbpOpening ?? 0;
                        $lwbpOpening = $lwbpOpening ?? 0;

                        $wbpUsage = null;
                        $lwbpUsage = null;
                        $totalUsage = null;

                        // Hitung usage jika ada closing dan opening
                        if ($wbpClosing !== null && $wbpOpening !== null) {
                            $wbpUsage = round($wbpClosing - $wbpOpening, 2);
                        }
                        if ($lwbpClosing !== null && $lwbpOpening !== null) {
                            $lwbpUsage = round($lwbpClosing - $lwbpOpening, 2);
                        }
                        // Hitung total jika minimal salah satu usage ada
                        if ($wbpUsage !== null || $lwbpUsage !== null) {
                            $totalUsage = round(($wbpUsage ?? 0) + ($lwbpUsage ?? 0), 2);
                        }

                        $electricityData[] = [
                            'location' => $displayName,
                            'wbp_opening' => $wbpOpening,
                            'lwbp_opening' => $lwbpOpening,
                            'wbp_closing' => $wbpClosing,
                            'lwbp_closing' => $lwbpClosing,
                            'wbp_usage' => $wbpUsage,
                            'lwbp_usage' => $lwbpUsage,
                            'total_usage' => $totalUsage,
                            'photo_path' => $electricityReading->photo_wbp ? $electricityReading->photo_wbp : null,
                            'photo_wbp_path' => $electricityReading->photo_wbp ? $electricityReading->photo_wbp : null,
                            'photo_lwbp_path' => $electricityReading->photo_lwbp ? $electricityReading->photo_lwbp : null,
                        ];

                        $previousClosings['electricity'][$meterId] = [
                            'wbp' => $wbpClosing,
                            'lwbp' => $lwbpClosing,
                        ];
                    }
                    $rowData['electricity'] = $electricityData;
                }

                $reportData[] = $rowData;
            }

            $branch = \App\Models\Branch::find($request['branch_id']);

            $pdf = Pdf::loadView('daily-record.daily-usage-report-pdf', [
                'reportData' => $reportData,
                'category' => $category,
                'branch' => $branch,
                'filters' => $request
            ]);

            $categoryLabel = match ($category) {
                'gas' => 'Gas',
                'water' => 'Air',
                'electricity' => 'Listrik',
                default => 'Daily Usage'
            };

            $filename = 'laporan-daily-usage-' . strtolower($categoryLabel) . '-' . date('Y-m-d-H-i-s') . '.pdf';
            $pdf->setPaper('a4', 'landscape');

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat export PDF: ' . $e->getMessage(), null, 500);
        }
    }
    /**
     * Initialize previous closing readings based on the last record before the selected start date.
     * Delegates to DailyUsageReportService for the actual logic.
     */
    private function initializePreviousClosings(array $filters): array
    {
        return $this->reportService->initializePreviousClosings($filters);
    }
}
