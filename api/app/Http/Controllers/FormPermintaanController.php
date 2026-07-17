<?php

namespace App\Http\Controllers;

use App\Exports\FormPermintaanExport;
use App\Helpers\ResponseHelper;
use App\Http\Requests\FormPermintaanStoreRequest;
use App\Http\Resources\FormPermintaanResource;
use App\Http\Resources\PaginateResource;
use App\Interfaces\FormPermintaanRepositoryInterface;
use App\Models\FormPermintaan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\PermissionMiddleware;

class FormPermintaanController extends Controller implements HasMiddleware
{
    private FormPermintaanRepositoryInterface $formPermintaanRepository;

    public function __construct(FormPermintaanRepositoryInterface $formPermintaanRepository)
    {
        $this->formPermintaanRepository = $formPermintaanRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['form-permintaan-list']), only: ['index', 'show', 'downloadPdf', 'exportPdf', 'exportExcel']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-confirm']), only: ['confirm', 'updateStatus']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-create']), only: ['store', 'uploadAttachment', 'downloadAttachment', 'deleteAttachment']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-delete']), only: ['destroy']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-review']), only: ['review']),
            new Middleware(PermissionMiddleware::using(['form-permintaan-reject']), only: ['reject']),
        ];
    }

    /**
     * Display a paginated listing of form permintaan.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'row_per_page' => 'required|integer',
            'search' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'request_type' => 'nullable|in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa',
            'status' => 'nullable|in:pending,approved,rejected,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $formPermintaan = $this->formPermintaanRepository->getAllPaginated(
                $request->search,
                $request->row_per_page,
                $request->branch_id,
                $request->request_type,
                $request->status,
                $request->start_date,
                $request->end_date
            );

            return ResponseHelper::jsonResponse(true, 'Data Form Permintaan Berhasil Diambil', PaginateResource::make($formPermintaan, FormPermintaanResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created form permintaan.
     */
    public function store(FormPermintaanStoreRequest $request): JsonResponse
    {
        $request = $request->validated();

        try {
            $formPermintaan = $this->formPermintaanRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Data Form Permintaan Berhasil Ditambahkan', new FormPermintaanResource($formPermintaan), 201);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Store Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Update the specified form permintaan.
     */
    public function update(FormPermintaanStoreRequest $request, string $id): JsonResponse
    {
        $request = $request->validated();

        try {
            $formPermintaan = $this->formPermintaanRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Form Permintaan Berhasil Diubah', new FormPermintaanResource($formPermintaan), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Update Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Remove the specified form permintaan.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->formPermintaanRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Data Form Permintaan Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Delete Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Update status of form permintaan via dropdown (admin).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:progress,approved,rejected',
        ]);

        try {
            $formPermintaan = $this->formPermintaanRepository->getById($id);

            $updateData = [
                'status' => $request->status,
            ];

            if ($request->status === 'approved') {
                $updateData['confirmed_by'] = auth()->id();
                $updateData['confirmed_at'] = now();
            } elseif ($request->status === 'rejected') {
                $updateData['rejected_by'] = auth()->id();
                $updateData['rejection_reason'] = $request->reason ?? null;
            }

            $formPermintaan->update($updateData);

            return ResponseHelper::jsonResponse(
                true,
                'Status form permintaan berhasil diubah',
                new FormPermintaanResource($formPermintaan->fresh(['user', 'branch', 'ticket', 'confirmedBy', 'items.attachments', 'attachments'])),
                200
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan UpdateStatus Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function confirm(string $id): JsonResponse
    {
        try {
            $formPermintaan = $this->formPermintaanRepository->confirm($id);

            return ResponseHelper::jsonResponse(true, 'Form permintaan berhasil dikonfirmasi', new FormPermintaanResource($formPermintaan), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Confirm Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Review the specified form permintaan.
     */
    public function review(string $id): JsonResponse
    {
        try {
            $formPermintaan = $this->formPermintaanRepository->getById($id);

            if ($formPermintaan->status !== 'pending') {
                return ResponseHelper::jsonResponse(false, 'Hanya form permintaan dengan status pending yang dapat direview.', null, 422);
            }

            $formPermintaan->update([
                'status' => 'reviewed',
                'reviewed_by' => auth()->id(),
            ]);

            return ResponseHelper::jsonResponse(true, 'Form permintaan berhasil direview', new FormPermintaanResource($formPermintaan->fresh(['user', 'branch', 'ticket', 'confirmedBy', 'items.attachments', 'attachments'])), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Review Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Reject the specified form permintaan.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $formPermintaan = $this->formPermintaanRepository->getById($id);

            if (!in_array($formPermintaan->status, ['pending', 'reviewed'])) {
                return ResponseHelper::jsonResponse(false, 'Hanya form permintaan dengan status pending atau reviewed yang dapat ditolak.', null, 422);
            }

            $formPermintaan->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejection_reason' => $request->reason,
            ]);

            return ResponseHelper::jsonResponse(true, 'Form permintaan berhasil ditolak', new FormPermintaanResource($formPermintaan->fresh(['user', 'branch', 'ticket', 'confirmedBy', 'items.attachments', 'attachments'])), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Reject Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Display the specified form permintaan.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $formPermintaan = $this->formPermintaanRepository->getById($id);

            return ResponseHelper::jsonResponse(true, 'Data Form Permintaan Berhasil Diambil', new FormPermintaanResource($formPermintaan), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Upload an attachment for the specified form permintaan.
     */
    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx|max:10240',
            'form_permintaan_item_id' => 'nullable|integer|exists:form_permintaan_items,id',
        ]);

        try {
            $file = $request->file('file');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('form-permintaan', $fileName, 'public');

            $attachment = $this->formPermintaanRepository->addAttachment($id, [
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'form_permintaan_item_id' => $request->form_permintaan_item_id,
            ]);

            return ResponseHelper::jsonResponse(true, 'Attachment Form Permintaan Berhasil Ditambahkan', $attachment, 201);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Upload Attachment Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Delete an attachment from the specified form permintaan.
     */
    public function deleteAttachment(string $id, string $attachmentId): JsonResponse
    {
        try {
            $this->formPermintaanRepository->deleteAttachment($id, $attachmentId);

            return ResponseHelper::jsonResponse(true, 'Attachment Form Permintaan Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Attachment Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Delete Attachment Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    public function downloadAttachment(string $id, string $attachmentId)
    {
        try {
            $attachment = $this->formPermintaanRepository->getAttachment($id, $attachmentId);

            if (!$attachment->file_path || !Storage::disk('public')->exists($attachment->file_path)) {
                return ResponseHelper::jsonResponse(false, 'File attachment tidak ditemukan', null, 404);
            }

            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Attachment Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Download Attachment Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Export list of form permintaan as PDF with active filters.
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'request_type' => 'nullable|in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa',
            'status' => 'nullable|in:progress,pending,approved,rejected',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
        ]);

        try {
            $filters = [
                'search' => $request->search,
                'branch_id' => $request->branch_id,
                'request_type' => $request->request_type,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $records = $this->getFilteredRecords($filters);

            $pdf = Pdf::loadView('form-permintaan.export-list', ['records' => $records]);
            $pdf->setPaper('a4', 'landscape');

            $filename = 'form_permintaan_' . now()->format('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Export PDF Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Gagal export PDF: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export list of form permintaan as Excel with active filters.
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'request_type' => 'nullable|in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa',
            'status' => 'nullable|in:progress,pending,approved,rejected',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
        ]);

        try {
            $filters = [
                'search' => $request->search,
                'branch_id' => $request->branch_id,
                'request_type' => $request->request_type,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $export = new FormPermintaanExport($filters);
            return $export->download();
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Export Excel Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Gagal export Excel: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get filtered records for export (same logic as getAllPaginated without pagination).
     */
    private function getFilteredRecords(array $filters)
    {
        $user = Auth::user();

        $query = FormPermintaan::with(['user', 'branch', 'ticket'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Same access control as getAllPaginated
        if (!$user->can('form-permintaan-view-all')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            });
        }

        if (!empty($filters['search'])) {
            $query->where('request_number', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    /**
     * Generate and download PDF for the specified form permintaan.
     */
    public function downloadPdf(string $id)
    {
        try {
            $formPermintaan = $this->formPermintaanRepository->getById($id);

            if ($formPermintaan->status !== 'approved' && $formPermintaan->status !== 'completed') {
                return ResponseHelper::jsonResponse(false, 'Form permintaan belum disetujui.', null, 400);
            }

            $data = [
                'formPermintaan' => $formPermintaan,
            ];

            $pdf = Pdf::loadView('form-permintaan.pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $fileName = 'Form_Permintaan_' . str_replace('/', '_', $formPermintaan->request_number) . '.pdf';

            return $pdf->download($fileName);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Form Permintaan Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            Log::error('Form Permintaan Download PDF Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat mengunduh PDF: ' . $e->getMessage(), null, 500);
        }
    }
}
