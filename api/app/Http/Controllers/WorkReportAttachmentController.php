<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\WorkReportAttachmentStoreRequest;
use App\Interfaces\WorkReportAttachmentRepositoryInterface;
use App\Http\Resources\WorkReportAttachmentResource;
use App\Services\FileCompressionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkReportAttachmentController extends Controller implements HasMiddleware
{

    private WorkReportAttachmentRepositoryInterface $workReportAttachmentRepository;
    private FileCompressionService $fileCompressionService;

    public function __construct(
        WorkReportAttachmentRepositoryInterface $workReportAttachmentRepository,
        FileCompressionService $fileCompressionService
    ) {
        $this->workReportAttachmentRepository = $workReportAttachmentRepository;
        $this->fileCompressionService = $fileCompressionService;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['work-report-attachment-list']), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using(['work-report-attachment-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['work-report-attachment-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of attachments for a specific work report.
     */
    public function index(Request $request, string $workReportId)
    {
        try {
            $attachments = $this->workReportAttachmentRepository->getAllByWorkReportId($workReportId);

            return ResponseHelper::jsonResponse(true, 'Data Attachment Laporan Kerja Berhasil Diambil', WorkReportAttachmentResource::collection($attachments), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created attachment.
     */
    public function store(WorkReportAttachmentStoreRequest $request, string $workReportId)
    {
        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // Compress and store file (with 75% quality and max width 1920px)
            $filePath = $this->fileCompressionService->compressAndStore(
                $file,
                'work-report-attachments',
                $fileName,
                75,  // Quality (0-100)
                1920 // Max width in pixels
            );

            $data = [
                'work_report_id' => $workReportId,
                'file_path' => $filePath,
                'file_type' => $file->getClientMimeType(),
            ];

            $attachment = $this->workReportAttachmentRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Attachment Laporan Kerja Berhasil Ditambahkan', new WorkReportAttachmentResource($attachment), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(string $workReportId, string $id)
    {
        try {
            $attachment = $this->workReportAttachmentRepository->getById($id);

            // Verify the attachment belongs to the specified work report
            if ($attachment->work_report_id != $workReportId) {
                return ResponseHelper::jsonResponse(false, 'Attachment Tidak Ditemukan', null, 404);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $this->workReportAttachmentRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Attachment Laporan Kerja Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Attachment Laporan Kerja Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }
}
