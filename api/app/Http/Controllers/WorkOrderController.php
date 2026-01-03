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
use OpenApi\Annotations as OA;

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
     * @OA\Get(
     *     path="/work-orders",
     *     tags={"Work Orders"},
     *     summary="Get all work orders",
     *     description="Get a list of all work orders",
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
     *                         @OA\Items(ref="#/components/schemas/WorkOrder")
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

    /**
     * @OA\Get(
     *     path="/work-orders/all/paginated",
     *     tags={"Work Orders"},
     *     summary="Get paginated work orders",
     *     description="Get a paginated list of work orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=true, @OA\Schema(type="integer")),
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
     * @OA\Post(
     *     path="/work-orders",
     *     tags={"Work Orders"},
     *     summary="Create work order",
     *     description="Create a new work order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ticket_id", "status"},
     *             @OA\Property(property="ticket_id", type="string", description="UUID of the ticket"),
     *             @OA\Property(property="status", type="string", enum={"pending", "in_progress", "done", "cancelled"}),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="user_id", type="integer", description="Assigned user ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Work Order created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkOrder")
     *                 )
     *             }
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/work-orders/{id}",
     *     tags={"Work Orders"},
     *     summary="Get work order by ID",
     *     description="Get a specific work order by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkOrder")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/work-orders/{id}",
     *     tags={"Work Orders"},
     *     summary="Update work order",
     *     description="Update an existing work order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending", "in_progress", "done", "cancelled"}),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Work Order updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkOrder")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/work-orders/{id}",
     *     tags={"Work Orders"},
     *     summary="Delete work order",
     *     description="Delete a work order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Work Order deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/work-orders/ticket/{ticketId}",
     *     tags={"Work Orders"},
     *     summary="Get work order by ticket ID",
     *     description="Get work order associated with a ticket",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ticketId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/WorkOrder")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/work-orders/{id}/pdf",
     *     tags={"Work Orders"},
     *     summary="Download Work Order PDF",
     *     description="Generate and download PDF for work order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Work Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
