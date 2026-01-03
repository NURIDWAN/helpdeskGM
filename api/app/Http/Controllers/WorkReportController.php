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

class WorkReportController extends Controller implements HasMiddleware
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
     * @OA\Get(
     *     path="/work-reports",
     *     tags={"Work Reports"},
     *     summary="Get all work reports",
     *     description="Get a list of all work reports",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
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
     *                         @OA\Items(ref="#/components/schemas/WorkReport")
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

    /**
     * @OA\Get(
     *     path="/work-reports/all/paginated",
     *     tags={"Work Reports"},
     *     summary="Get paginated work reports",
     *     description="Get a paginated list of work reports with filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="branch_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="job_template_id", in="query", required=false, @OA\Schema(type="integer")),
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
     * @OA\Post(
     *     path="/work-reports",
     *     tags={"Work Reports"},
     *     summary="Create work report",
     *     description="Create a new work report",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"work_order_id", "description", "work_date"},
     *             @OA\Property(property="work_order_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="work_date", type="string", format="date"),
     *             @OA\Property(property="custom_job", type="string", description="Optional custom job description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Work Report created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkReport")
     *                 )
     *             }
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/work-reports/{id}",
     *     tags={"Work Reports"},
     *     summary="Get work report by ID",
     *     description="Get a specific work report by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkReport")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Report not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/work-reports/{id}",
     *     tags={"Work Reports"},
     *     summary="Update work report",
     *     description="Update an existing work report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="work_order_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="work_date", type="string", format="date"),
     *             @OA\Property(property="custom_job", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Work Report updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkReport")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Report not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/work-reports/{id}",
     *     tags={"Work Reports"},
     *     summary="Delete work report",
     *     description="Delete a work report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Work Report deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Report not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Post(
     *     path="/work-reports/export-pdf",
     *     tags={"Work Reports"},
     *     summary="Export work reports to PDF",
     *     description="Export filtered work reports to PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="search", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="job_template_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF Download",
     *         @OA\MediaType(mediaType="application/pdf")
     *     )
     * )
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
     * @OA\Post(
     *     path="/work-reports/export-excel",
     *     tags={"Work Reports"},
     *     summary="Export work reports to Excel",
     *     description="Export filtered work reports to Excel",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="search", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="job_template_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel Download",
     *         @OA\MediaType(mediaType="text/csv")
     *     )
     * )
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
