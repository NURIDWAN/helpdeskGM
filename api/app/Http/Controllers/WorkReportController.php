<?php

namespace App\Http\Controllers;

use App\Interfaces\WorkReportRepositoryInterface;
use App\Http\Requests\WorkReportStoreRequest;
use App\Http\Requests\WorkReportUpdateRequest;
use App\Http\Resources\WorkReportResource;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class WorkReportController extends Controller  implements HasMiddleware
{
    protected $workReportRepository;

    public function __construct(WorkReportRepositoryInterface $workReportRepository)
    {
        $this->workReportRepository = $workReportRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['work-report-list|work-report-create|work-report-edit|work-report-delete']), only: ['index', 'getAllPaginated', 'show', 'exportPdf', 'exportExcel']),
            new Middleware(PermissionMiddleware::using(['work-report-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['work-report-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['work-report-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $workReports = $this->workReportRepository->getAll(
                $request->search,
                $request->limit,
                true
            );

            return ResponseHelper::jsonResponse(true, 'Data Work Report Berhasil Diambil', WorkReportResource::collection($workReports), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    public function getAllPaginated(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'row_per_page' => 'required|integer',
            'status' => 'nullable|string',
            'branch_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'job_template_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $workReports = $this->workReportRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page'],
                $request['status'] ?? null,
                $request['branch_id'] ?? null,
                $request['user_id'] ?? null,
                $request['job_template_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            return ResponseHelper::jsonResponse(true, 'Data Work Report Berhasil Diambil', PaginateResource::make($workReports, WorkReportResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkReportStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $workReport = $this->workReportRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Work Report Berhasil Dibuat', new WorkReportResource($workReport), 201);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat membuat Work Report', null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $workReport = $this->workReportRepository->getById($id);

            return ResponseHelper::jsonResponse(true, 'Data Work Report Berhasil Diambil', new WorkReportResource($workReport), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Report tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkReportUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $workReport = $this->workReportRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Work Report Berhasil Diubah', new WorkReportResource($workReport), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Report tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat memperbarui Work Report', null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->workReportRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Work Report Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Report tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat menghapus Work Report', null, 500);
        }
    }

    /**
     * Export work reports to PDF
     */
    public function exportPdf(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'branch_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'job_template_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $workReports = $this->workReportRepository->getExportData(
                $request['search'] ?? null,
                $request['status'] ?? null,
                $request['branch_id'] ?? null,
                $request['user_id'] ?? null,
                $request['job_template_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $pdf = Pdf::loadView('workorder.work-report-pdf', [
                'workReports' => $workReports,
                'filters' => $request
            ]);

            $filename = 'laporan-kerja-' . date('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Export work reports to Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'branch_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'job_template_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $workReports = $this->workReportRepository->getExportData(
                $request['search'] ?? null,
                $request['status'] ?? null,
                $request['branch_id'] ?? null,
                $request['user_id'] ?? null,
                $request['job_template_id'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            $filename = 'laporan-kerja-' . date('Y-m-d-H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($workReports) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Headers
                fputcsv($file, [
                    'ID',
                    'User',
                    'Cabang',
                    'Jenis Pekerjaan',
                    'Pekerjaan Lainnya',
                    'Status',
                    'Tanggal Dibuat',
                    'Tanggal Diperbarui'
                ]);

                // Data
                foreach ($workReports as $report) {
                    fputcsv($file, [
                        $report->id,
                        $report->user->name ?? '-',
                        $report->branch->name ?? '-',
                        $report->jobTemplate->name ?? '-',
                        $report->custom_job ?? '-',
                        $report->status->label(),
                        $report->created_at->format('d/m/Y H:i'),
                        $report->updated_at->format('d/m/Y H:i')
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
