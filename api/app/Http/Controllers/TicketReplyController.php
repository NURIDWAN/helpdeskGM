<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\TicketReplyStoreRequest;
use App\Http\Requests\TicketReplyUpdateRequest;
use App\Interfaces\TicketReplyRepositoryInterface;
use App\Http\Resources\TicketReplyResource;
use App\Http\Resources\PaginateResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class TicketReplyController extends Controller implements HasMiddleware
{

    private TicketReplyRepositoryInterface $ticketReplyRepository;

    public function __construct(TicketReplyRepositoryInterface $ticketReplyRepository)
    {
        $this->ticketReplyRepository = $ticketReplyRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['ticket-reply-list']), only: ['index', 'getAllPaginated', 'show']),
            new Middleware(PermissionMiddleware::using(['ticket-reply-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['ticket-reply-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['ticket-reply-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of replies for a specific ticket.
     */
    public function index(Request $request, string $ticketId)
    {
        try {
            $user = Auth::user();

            // Visibility: admin all, staff only assigned, user only own ticket
            if (!$this->canAccessTicket($user, $ticketId)) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki akses ke tiket ini', null, 403);
            }

            $replies = $this->ticketReplyRepository->getAllByTicketId($ticketId);

            return ResponseHelper::jsonResponse(true, 'Data Balasan Tiket Berhasil Diambil', TicketReplyResource::collection($replies), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created reply.
     */
    public function store(TicketReplyStoreRequest $request, string $ticketId)
    {
        $request = $request->validated();

        try {
            $user = Auth::user();

            if (!$this->canAccessTicket($user, $ticketId)) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki akses ke tiket ini', null, 403);
            }

            $data = [
                'ticket_id' => $ticketId,
                'user_id' => $user?->id,
                'content' => $request['content'],
            ];

            $reply = $this->ticketReplyRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Balasan Tiket Berhasil Ditambahkan', new TicketReplyResource($reply), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi Kesalahan', null, 500);
        }
    }

    /**
     * Display the specified reply.
     */
    public function show(string $ticketId, string $id)
    {
        try {
            $user = Auth::user();
            $reply = $this->ticketReplyRepository->getById($id);

            if (!$this->canAccessTicket($user, $ticketId)) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki akses ke tiket ini', null, 403);
            }

            // Verify the reply belongs to the specified ticket
            if ($reply->ticket_id != $ticketId) {
                return ResponseHelper::jsonResponse(false, 'Balasan Tidak Ditemukan', null, 404);
            }

            return ResponseHelper::jsonResponse(true, 'Data Balasan Tiket Berhasil Diambil', new TicketReplyResource($reply), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Balasan Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified reply.
     */
    public function update(TicketReplyUpdateRequest $request, string $ticketId, string $id)
    {
        $request = $request->validated();

        try {
            $user = Auth::user();
            $reply = $this->ticketReplyRepository->getById($id);

            if (!$this->canAccessTicket($user, $ticketId)) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki akses ke tiket ini', null, 403);
            }

            // Verify the reply belongs to the specified ticket
            if ($reply->ticket_id != $ticketId) {
                return ResponseHelper::jsonResponse(false, 'Balasan Tidak Ditemukan', null, 404);
            }

            // Check if user can edit this reply (only the author can edit)
            if ($reply->user_id != $user?->id) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki izin untuk mengedit balasan ini', null, 403);
            }

            $reply = $this->ticketReplyRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Balasan Tiket Berhasil Diubah', new TicketReplyResource($reply), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Balasan Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Remove the specified reply.
     */
    public function destroy(string $ticketId, string $id)
    {
        try {
            $user = Auth::user();
            $reply = $this->ticketReplyRepository->getById($id);

            if (!$this->canAccessTicket($user, $ticketId)) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki akses ke tiket ini', null, 403);
            }

            // Verify the reply belongs to the specified ticket
            if ($reply->ticket_id != $ticketId) {
                return ResponseHelper::jsonResponse(false, 'Balasan Tidak Ditemukan', null, 404);
            }

            // Check if user can delete this reply (only the author can delete)
            if ($reply->user_id != $user?->id) {
                return ResponseHelper::jsonResponse(false, 'Anda tidak memiliki izin untuk menghapus balasan ini', null, 403);
            }

            $this->ticketReplyRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Balasan Tiket Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Balasan Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    private function canAccessTicket($user, string $ticketId): bool
    {
        if (!$user) return false;

        if ($user->hasRole('admin')) {
            return true;
        }

        // Staff: ticket assigned to them
        if ($user->hasRole('staff')) {
            return Ticket::where('id', $ticketId)
                ->whereHas('assignedStaff', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
        }

        // Regular user: only own tickets
        return Ticket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
