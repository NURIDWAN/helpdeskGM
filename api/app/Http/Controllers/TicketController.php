<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Requests\TicketUpdateRequest;
use App\Interfaces\TicketRepositoryInterface;
use App\Http\Resources\TicketResource;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class TicketController extends Controller implements HasMiddleware
{

    private TicketRepositoryInterface $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['ticket-list|ticket-create|ticket-edit|ticket-delete']), only: ['index', 'getAllPaginated', 'show']),
            new Middleware(PermissionMiddleware::using(['ticket-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['ticket-edit|ticket-update-status']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['ticket-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $tickets = $this->ticketRepository->getAll(
                $request->search,
                $request->limit,
                true,
                $request->status,
                $request->priority,
                $request->branch_id,
                $request->assigned_to,
                $request->start_date,
                $request->end_date
            );

            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Diambil', TicketResource::collection($tickets), 200);
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
            'priority' => 'nullable|string',
            'branch_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        try {
            $tickets = $this->ticketRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page'],
                $request['status'] ?? null,
                $request['priority'] ?? null,
                $request['branch_id'] ?? null,
                $request['assigned_to'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null
            );

            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Diambil', PaginateResource::make($tickets, TicketResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $ticket = $this->ticketRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Ditambahkan', new TicketResource($ticket), 201);
        } catch (\Throwable $e) {
            Log::error('Ticket Store Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 400);
        }
    }

    /**
     * Display the specified resource by ID (for apiResource).
     */
    public function show(string $id)
    {
        try {
            $ticket = $this->ticketRepository->getById($id);
            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Diambil', new TicketResource($ticket), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Display the specified resource by code.
     */
    public function showByCode(string $code)
    {
        try {
            $ticket = $this->ticketRepository->getByCode($code);
            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Diambil', new TicketResource($ticket), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $ticket = $this->ticketRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Diubah', new TicketResource($ticket), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->ticketRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Data Tiket Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Tiket Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Export tickets to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $filters = [
                'status' => $request->status,
                'priority' => $request->priority,
                'branch_id' => $request->branch_id,
                'date_from' => $request->date_from ?? $request->start_date,
                'date_to' => $request->date_to ?? $request->end_date,
                'search' => $request->search,
                'duration' => $request->duration,
            ];

            $export = new \App\Exports\TicketExport($filters);
            return $export->download();
        } catch (\Throwable $e) {
            Log::error('Export Excel Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Gagal export: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export tickets to PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $query = \App\Models\Ticket::with(['user', 'branch', 'assignedStaff']);

            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->priority) {
                $query->where('priority', $request->priority);
            }
            if ($request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->date_from ?? $request->start_date) {
                $query->whereDate('created_at', '>=', $request->date_from ?? $request->start_date);
            }
            if ($request->date_to ?? $request->end_date) {
                $query->whereDate('created_at', '<=', $request->date_to ?? $request->end_date);
            }
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            }
            if ($request->duration) {
                $duration = $request->duration;
                if ($duration === 'today') {
                    $query->whereDate('created_at', now());
                } elseif ($duration === 'week') {
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($duration === 'month') {
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                }
            }

            $tickets = $query->orderBy('created_at', 'desc')->get();

            // Get branch name for filter display
            $branchName = null;
            if ($request->branch_id) {
                $branch = \App\Models\Branch::find($request->branch_id);
                $branchName = $branch?->name;
            }

            $filters = [
                'status' => $request->status,
                'priority' => $request->priority,
                'branch' => $branchName,
                'date_from' => $request->date_from ?? $request->start_date,
                'date_to' => $request->date_to ?? $request->end_date,
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.tickets-pdf', [
                'tickets' => $tickets,
                'filters' => $filters,
            ]);

            $pdf->setPaper('a4', 'landscape');

            return $pdf->download('tickets_' . now()->format('Y-m-d_His') . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Export PDF Error: ' . $e->getMessage());
            return ResponseHelper::jsonResponse(false, 'Gagal export: ' . $e->getMessage(), null, 500);
        }
    }
}
