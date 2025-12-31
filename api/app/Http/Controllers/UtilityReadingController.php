<?php

namespace App\Http\Controllers;

use App\Interfaces\UtilityReadingRepositoryInterface;
use App\Http\Requests\UtilityReadingStoreRequest;
use App\Http\Requests\UtilityReadingUpdateRequest;
use App\Http\Resources\UtilityReadingResource;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Services\FileCompressionService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UtilityReadingController extends Controller implements HasMiddleware
{
    protected $utilityReadingRepository;
    protected $fileCompressionService;

    public function __construct(
        UtilityReadingRepositoryInterface $utilityReadingRepository,
        FileCompressionService $fileCompressionService
    ) {
        $this->utilityReadingRepository = $utilityReadingRepository;
        $this->fileCompressionService = $fileCompressionService;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['utility-reading-list|utility-reading-create|utility-reading-edit|utility-reading-delete']), only: ['index', 'getAllPaginated', 'show', 'exportPdf', 'exportExcel']),
            new Middleware(PermissionMiddleware::using(['utility-reading-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['utility-reading-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['utility-reading-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ?string $dailyRecordId = null)
    {
        try {
            // If called from nested route, use the dailyRecordId from route parameter
            $filterDailyRecordId = $dailyRecordId ?? $request->daily_record_id;

            $utilityReadings = $this->utilityReadingRepository->getAll(
                $request->search,
                $request->limit,
                true,
                $filterDailyRecordId,
                $request->category
            );

            return ResponseHelper::jsonResponse(true, 'Data Utility Reading Berhasil Diambil', UtilityReadingResource::collection($utilityReadings), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    public function getAllPaginated(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'row_per_page' => 'required|integer',
            'daily_record_id' => 'nullable|integer',
            'category' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $utilityReadings = $this->utilityReadingRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page'],
                $request['daily_record_id'] ?? null,
                $request['category'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            return ResponseHelper::jsonResponse(true, 'Data Utility Reading Berhasil Diambil', PaginateResource::make($utilityReadings, UtilityReadingResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UtilityReadingStoreRequest $request, ?string $dailyRecordId = null)
    {
        try {
            $data = $request->validated();

            // If called from nested route, use the dailyRecordId from route parameter
            if ($dailyRecordId) {
                $data['daily_record_id'] = $dailyRecordId;
            }

            // Handle photo upload based on category
            $category = $data['category'] ?? null;

            if ($category === 'electricity') {
                // For electricity: handle photo_wbp and photo_lwbp
                if ($request->hasFile('photo_wbp')) {
                    $file = $request->file('photo_wbp');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo_wbp'] = $filePath;
                } else {
                    $data['photo_wbp'] = null;
                }

                if ($request->hasFile('photo_lwbp')) {
                    $file = $request->file('photo_lwbp');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo_lwbp'] = $filePath;
                } else {
                    $data['photo_lwbp'] = null;
                }

                // Set regular photo to null for electricity
                $data['photo'] = null;
            } else {
                // For other categories: handle regular photo
                if ($request->hasFile('photo')) {
                    $file = $request->file('photo');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo'] = $filePath;
                } else {
                    $data['photo'] = null;
                }

                // Set electricity photos to null for non-electricity
                $data['photo_wbp'] = null;
                $data['photo_lwbp'] = null;
            }

            $utilityReading = $this->utilityReadingRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Utility Reading Berhasil Dibuat', new UtilityReadingResource($utilityReading), 201);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat membuat Utility Reading', null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $utilityReading = $this->utilityReadingRepository->getById($id);

            return ResponseHelper::jsonResponse(true, 'Data Utility Reading Berhasil Diambil', new UtilityReadingResource($utilityReading), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Utility Reading tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UtilityReadingUpdateRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $utilityReading = $this->utilityReadingRepository->getById($id);

            // Handle photo upload based on category
            $category = $data['category'] ?? $utilityReading->category;

            if ($category === 'electricity') {
                // For electricity: handle photo_wbp and photo_lwbp
                if ($request->hasFile('photo_wbp')) {
                    // Delete old photo_wbp if exists
                    if ($utilityReading->photo_wbp && Storage::disk('public')->exists($utilityReading->photo_wbp)) {
                        Storage::disk('public')->delete($utilityReading->photo_wbp);
                    }

                    $file = $request->file('photo_wbp');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo_wbp'] = $filePath;
                } else {
                    // Keep existing photo_wbp if no new photo provided
                    unset($data['photo_wbp']);
                }

                if ($request->hasFile('photo_lwbp')) {
                    // Delete old photo_lwbp if exists
                    if ($utilityReading->photo_lwbp && Storage::disk('public')->exists($utilityReading->photo_lwbp)) {
                        Storage::disk('public')->delete($utilityReading->photo_lwbp);
                    }

                    $file = $request->file('photo_lwbp');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo_lwbp'] = $filePath;
                } else {
                    // Keep existing photo_lwbp if no new photo provided
                    unset($data['photo_lwbp']);
                }

                // Delete old regular photo if exists (shouldn't be used for electricity)
                if ($utilityReading->photo && Storage::disk('public')->exists($utilityReading->photo)) {
                    Storage::disk('public')->delete($utilityReading->photo);
                }
                $data['photo'] = null;
            } else {
                // For other categories: handle regular photo
                if ($request->hasFile('photo')) {
                    // Delete old photo if exists
                    if ($utilityReading->photo && Storage::disk('public')->exists($utilityReading->photo)) {
                        Storage::disk('public')->delete($utilityReading->photo);
                    }

                    $file = $request->file('photo');
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;

                    $filePath = $this->fileCompressionService->compressAndStore(
                        $file,
                        'utility-readings',
                        $fileName,
                        75,
                        1920
                    );

                    $data['photo'] = $filePath;
                } else {
                    // Start Validation: If no new photo AND no existing photo, fail
                    if (!$utilityReading->photo) {
                        return ResponseHelper::jsonResponse(false, 'Foto meter wajib diisi', null, 422);
                    }
                    // End Validation

                    // Keep existing photo if no new photo provided
                    unset($data['photo']);
                }

                // Delete old electricity photos if exists (shouldn't be used for non-electricity)
                if ($utilityReading->photo_wbp && Storage::disk('public')->exists($utilityReading->photo_wbp)) {
                    Storage::disk('public')->delete($utilityReading->photo_wbp);
                }
                if ($utilityReading->photo_lwbp && Storage::disk('public')->exists($utilityReading->photo_lwbp)) {
                    Storage::disk('public')->delete($utilityReading->photo_lwbp);
                }
                $data['photo_wbp'] = null;
                $data['photo_lwbp'] = null;
            }

            $utilityReading = $this->utilityReadingRepository->update($id, $data);

            return ResponseHelper::jsonResponse(true, 'Data Utility Reading Berhasil Diubah', new UtilityReadingResource($utilityReading), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Utility Reading tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat memperbarui Utility Reading', null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->utilityReadingRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Utility Reading Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Utility Reading tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat menghapus Utility Reading', null, 500);
        }
    }

    /**
     * Export utility readings to PDF
     */
    public function exportPdf(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'daily_record_id' => 'nullable|integer',
            'category' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $utilityReadings = $this->utilityReadingRepository->getExportData(
                $request['search'] ?? null,
                $request['daily_record_id'] ?? null,
                $request['category'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $pdf = Pdf::loadView('utility-reading.utility-reading-pdf', [
                'utilityReadings' => $utilityReadings,
                'filters' => $request
            ]);

            $filename = 'pembacaan-utilitas-' . date('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Export utility readings to Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'daily_record_id' => 'nullable|integer',
            'category' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $utilityReadings = $this->utilityReadingRepository->getExportData(
                $request['search'] ?? null,
                $request['daily_record_id'] ?? null,
                $request['category'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $filename = 'pembacaan-utilitas-' . date('Y-m-d-H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($utilityReadings) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Headers
                fputcsv($file, [
                    'ID',
                    'Daily Record ID',
                    'User',
                    'Cabang',
                    'Kategori',
                    'Sub Tipe',
                    'Lokasi',
                    'Nilai Meter',
                    'Tanggal Dibuat',
                    'Tanggal Diperbarui'
                ]);

                // Data
                foreach ($utilityReadings as $reading) {
                    fputcsv($file, [
                        $reading->id,
                        $reading->daily_record_id,
                        $reading->dailyRecord->user->name ?? '-',
                        $reading->dailyRecord->branch->name ?? '-',
                        $reading->category->label() ?? '-',
                        $reading->sub_type->label() ?? '-',
                        $reading->location ?? '-',
                        $reading->meter_value ?? '-',
                        $reading->created_at->format('d/m/Y H:i'),
                        $reading->updated_at->format('d/m/Y H:i')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat export Excel', null, 500);
        }
    }
}

