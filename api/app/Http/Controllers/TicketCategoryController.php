<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use OpenApi\Annotations as OA;

class TicketCategoryController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['ticket-category-list|ticket-category-create|ticket-category-edit|ticket-category-delete']), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using(['ticket-category-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['ticket-category-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['ticket-category-delete']), only: ['destroy']),
        ];
    }

    /**
     * @OA\Get(
     *     path="/ticket-categories",
     *     tags={"Ticket Categories"},
     *     summary="Get all ticket categories",
     *     description="Get a list of all ticket categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=false, @OA\Schema(type="string", description="Number of rows per page or 'all'")),
     *     @OA\Parameter(name="is_active", in="query", required=false, @OA\Schema(type="boolean")),
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
     *                         @OA\Items(ref="#/components/schemas/TicketCategory")
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = TicketCategory::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Order
        $query->ordered();

        // Pagination
        $perPage = $request->input('row_per_page', 10);
        if ($perPage === 'all') {
            $categories = $query->get();
            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/ticket-categories/{id}",
     *     tags={"Ticket Categories"},
     *     summary="Get ticket category by ID",
     *     description="Get a specific ticket category by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TicketCategory")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket Category not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(TicketCategory $ticketCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $ticketCategory,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/ticket-categories",
     *     tags={"Ticket Categories"},
     *     summary="Create ticket category",
     *     description="Create a new ticket category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="icon", type="string"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="sort_order", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket Category created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TicketCategory")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $category = TicketCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori tiket berhasil dibuat',
            'data' => $category,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/ticket-categories/{id}",
     *     tags={"Ticket Categories"},
     *     summary="Update ticket category",
     *     description="Update an existing ticket category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="icon", type="string"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="sort_order", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket Category updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TicketCategory")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket Category not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, TicketCategory $ticketCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name,' . $ticketCategory->id,
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $ticketCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori tiket berhasil diperbarui',
            'data' => $ticketCategory,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/ticket-categories/{id}",
     *     tags={"Ticket Categories"},
     *     summary="Delete ticket category",
     *     description="Delete a ticket category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket Category deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete category with tickets",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(TicketCategory $ticketCategory): JsonResponse
    {
        // Check if category has tickets
        $ticketCount = $ticketCategory->tickets()->count();
        if ($ticketCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat menghapus kategori. Terdapat {$ticketCount} tiket yang menggunakan kategori ini.",
            ], 422);
        }

        $ticketCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori tiket berhasil dihapus',
        ]);
    }
}
