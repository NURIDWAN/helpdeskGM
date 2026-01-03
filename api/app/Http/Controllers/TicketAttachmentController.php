<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\TicketAttachmentStoreRequest;
use App\Interfaces\TicketAttachmentRepositoryInterface;
use App\Http\Resources\TicketAttachmentResource;
use App\Services\FileCompressionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class TicketAttachmentController extends Controller implements HasMiddleware
{

    private TicketAttachmentRepositoryInterface $ticketAttachmentRepository;
    private FileCompressionService $fileCompressionService;

    public function __construct(
        TicketAttachmentRepositoryInterface $ticketAttachmentRepository,
        FileCompressionService $fileCompressionService
    ) {
        $this->ticketAttachmentRepository = $ticketAttachmentRepository;
        $this->fileCompressionService = $fileCompressionService;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['ticket-attachment-list']), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using(['ticket-attachment-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['ticket-attachment-delete']), only: ['destroy']),
        ];
    }

    /**
     * @OA\Get(
     *     path="/tickets/{ticketId}/attachments",
     *     tags={"Ticket Attachments"},
     *     summary="Get all attachments for a ticket",
     *     description="Get a list of all attachments for a specific ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ticketId", in="path", required=true, @OA\Schema(type="string")),
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
     *                         @OA\Items(ref="#/components/schemas/TicketAttachment")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request, string $ticketId)
    {
        try {
            $attachments = $this->ticketAttachmentRepository->getAllByTicketId($ticketId);

            return ResponseHelper::jsonResponse(true, 'Data Attachment Tiket Berhasil Diambil', TicketAttachmentResource::collection($attachments), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/tickets/{ticketId}/attachments",
     *     tags={"Ticket Attachments"},
     *     summary="Create ticket attachment",
     *     description="Upload a new attachment for a ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ticketId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attachment created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TicketAttachment")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(TicketAttachmentStoreRequest $request, string $ticketId)
    {
        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // Compress and store file (with 75% quality and max width 1920px)
            $filePath = $this->fileCompressionService->compressAndStore(
                $file,
                'ticket-attachments',
                $fileName,
                75,  // Quality (0-100)
                1920 // Max width in pixels
            );

            $data = [
                'ticket_id' => $ticketId,
                'file_path' => $filePath,
                'file_type' => $file->getClientMimeType(),
            ];

            $attachment = $this->ticketAttachmentRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Attachment Tiket Berhasil Ditambahkan', new TicketAttachmentResource($attachment), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/tickets/{ticketId}/attachments/{id}",
     *     tags={"Ticket Attachments"},
     *     summary="Delete ticket attachment",
     *     description="Delete a ticket attachment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ticketId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Attachment deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attachment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(string $ticketId, string $id)
    {
        try {
            $attachment = $this->ticketAttachmentRepository->getById($id);

            // Verify the attachment belongs to the specified ticket
            if ($attachment->ticket_id != $ticketId) {
                return ResponseHelper::jsonResponse(false, 'Attachment Tidak Ditemukan', null, 404);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $this->ticketAttachmentRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Attachment Tiket Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Attachment Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }
}
