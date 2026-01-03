<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\BranchStoreRequest;
use App\Http\Requests\BranchUpdateRequest;
use App\Interfaces\BranchRepositoryInterface;
use App\Http\Resources\BranchResource;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class BranchController extends Controller implements HasMiddleware
{

    private BranchRepositoryInterface $branchRepository;

    public function __construct(BranchRepositoryInterface $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['branch-list|branch-create|branch-edit|branch-delete']), only: ['index', 'getAllPaginated', 'show']),
            new Middleware(PermissionMiddleware::using(['branch-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['branch-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['branch-delete']), only: ['destroy']),
        ];
    }

    /**
     * @OA\Get(
     *     path="/branches",
     *     tags={"Branches"},
     *     summary="Get all branches",
     *     description="Get a list of all branches",
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
     *                         @OA\Items(ref="#/components/schemas/Branch")
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
            $branches = $this->branchRepository->getAll(
                $request->search,
                $request->limit,
                true
            );

            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Diambil', BranchResource::collection($branches), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/branches/all/paginated",
     *     tags={"Branches"},
     *     summary="Get paginated branches",
     *     description="Get a paginated list of branches",
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
            $branches = $this->branchRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page']
            );

            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Diambil', PaginateResource::make($branches, BranchResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/branches",
     *     tags={"Branches"},
     *     summary="Create branch",
     *     description="Create a new branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Branch created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Branch")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function store(BranchStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $branch = $this->branchRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Ditambahkan', new BranchResource($branch), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/branches/{id}",
     *     tags={"Branches"},
     *     summary="Get branch by ID",
     *     description="Get a specific branch by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Branch")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Branch not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $branch = $this->branchRepository->getById($id);
            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Diambil', new BranchResource($branch), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Branch Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/branches/{id}",
     *     tags={"Branches"},
     *     summary="Update branch",
     *     description="Update an existing branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Branch updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Branch")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Branch not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(BranchUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $branch = $this->branchRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Diubah', new BranchResource($branch), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Branch Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/branches/{id}",
     *     tags={"Branches"},
     *     summary="Delete branch",
     *     description="Delete a branch",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Branch deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Branch not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $this->branchRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Data Branch Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Branch Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }
}
