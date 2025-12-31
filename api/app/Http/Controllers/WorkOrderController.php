<?php

namespace App\Http\Controllers;

use App\Interfaces\WorkOrderRepositoryInterface;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Http\Resources\WorkOrderResource;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class WorkOrderController extends Controller implements HasMiddleware
{
    protected $workOrderRepository;

    public function __construct(WorkOrderRepositoryInterface $workOrderRepository)
    {
        $this->workOrderRepository = $workOrderRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['work-order-list|work-order-create|work-order-edit|work-order-delete|work-order-update-status']), only: ['index', 'getAllPaginated', 'show']),
            new Middleware(PermissionMiddleware::using(['work-order-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['work-order-edit|work-order-update-status']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['work-order-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $workOrders = $this->workOrderRepository->getAll(
                $request->search,
                $request->limit,
                true
            );

            return ResponseHelper::jsonResponse(true, 'Data Work Order Berhasil Diambil', WorkOrderResource::collection($workOrders), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    public function getAllPaginated(Request $request)
    {
        $request = $request->validate([
            'search' => 'nullable|string',
            'row_per_page' => 'required|integer'
        ]);

        try {
            $workOrders = $this->workOrderRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page']
            );

            return ResponseHelper::jsonResponse(true, 'Data Work Order Berhasil Diambil', PaginateResource::make($workOrders, WorkOrderResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkOrderStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $workOrder = $this->workOrderRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Work Order Berhasil Dibuat', new WorkOrderResource($workOrder), 201);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat membuat Work Order', null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $workOrder = $this->workOrderRepository->getById($id);

            return ResponseHelper::jsonResponse(true, 'Data Work Order Berhasil Diambil', new WorkOrderResource($workOrder), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Order tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkOrderUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $workOrder = $this->workOrderRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Work Order Berhasil Diubah', new WorkOrderResource($workOrder), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Order tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat memperbarui Work Order', null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->workOrderRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Work Order Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Order tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat menghapus Work Order', null, 500);
        }
    }

    /**
     * Get work order by ticket ID.
     */
    public function getByTicketId(string $ticketId)
    {
        try {
            $workOrder = $this->workOrderRepository->getByTicketId($ticketId);

            if (!$workOrder) {
                return ResponseHelper::jsonResponse(false, 'Work Order untuk tiket ini tidak ditemukan', null, 404);
            }

            return ResponseHelper::jsonResponse(true, 'Data Work Order Berhasil Diambil', new WorkOrderResource($workOrder), 200);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan saat mengambil data Work Order', null, 500);
        }
    }

    /**
     * Generate and download PDF for work order.
     */
    public function downloadPdf(string $id)
    {
        try {
            $workOrder = $this->workOrderRepository->getById($id);

            $data = [
                'workOrder' => $workOrder,
                'title' => 'SURAT PERINTAH KERJA'
            ];

            $pdf = Pdf::loadView('workorder.pdf', $data);
            $pdf->setPaper('a4', 'landscape');

            $fileName = 'SPK_' . str_replace('/', '_', $workOrder->number) . '.pdf';


            return $pdf->download($fileName);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Work Order tidak ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
