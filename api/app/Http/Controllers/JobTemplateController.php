<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\JobTemplateStoreRequest;
use App\Http\Requests\JobTemplateUpdateRequest;
use App\Interfaces\JobTemplateRepositoryInterface;
use App\Http\Resources\JobTemplateResource;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class JobTemplateController extends Controller implements HasMiddleware
{

    private JobTemplateRepositoryInterface $jobTemplateRepository;

    public function __construct(JobTemplateRepositoryInterface $jobTemplateRepository)
    {
        $this->jobTemplateRepository = $jobTemplateRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['job-template-list|job-template-create|job-template-edit|job-template-delete']), only: ['index', 'getAllPaginated', 'show']),
            new Middleware(PermissionMiddleware::using(['job-template-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['job-template-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['job-template-delete']), only: ['destroy']),
        ];
    }

    /**
     * @OA\Get(
     *     path="/job-templates",
     *     tags={"Job Templates"},
     *     summary="Get all job templates",
     *     description="Get a list of all job templates",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
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
     *                         @OA\Items(ref="#/components/schemas/JobTemplate")
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
            $jobTemplates = $this->jobTemplateRepository->getAll(
                $request->search,
                $request->limit,
                true,
                $request->is_active
            );

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Diambil', JobTemplateResource::collection($jobTemplates), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/job-templates/all/paginated",
     *     tags={"Job Templates"},
     *     summary="Get paginated job templates",
     *     description="Get a paginated list of job templates",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="row_per_page", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_active", in="query", required=false, @OA\Schema(type="boolean")),
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
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $jobTemplates = $this->jobTemplateRepository->getAllPaginated(
                $request['search'] ?? null,
                $request['row_per_page'],
                $request['is_active'] ?? null
            );

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Diambil', PaginateResource::make($jobTemplates, JobTemplateResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/job-templates",
     *     tags={"Job Templates"},
     *     summary="Create job template",
     *     description="Create a new job template",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="priority", type="string", enum={"Low","Medium","High"}),
     *             @OA\Property(property="checklist_items", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job Template created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/JobTemplate")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function store(JobTemplateStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $jobTemplate = $this->jobTemplateRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Ditambahkan', new JobTemplateResource($jobTemplate), 201);
        } catch (\Throwable $e) {
            \Log::error('JobTemplate Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request
            ]);
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/job-templates/{id}",
     *     tags={"Job Templates"},
     *     summary="Get job template by ID",
     *     description="Get a specific job template by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/JobTemplate")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job Template not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $jobTemplate = $this->jobTemplateRepository->getById($id);
            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Diambil', new JobTemplateResource($jobTemplate), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Template Job Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/job-templates/{id}",
     *     tags={"Job Templates"},
     *     summary="Update job template",
     *     description="Update an existing job template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="priority", type="string", enum={"Low","Medium","High"}),
     *             @OA\Property(property="checklist_items", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job Template updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/JobTemplate")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job Template not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(JobTemplateUpdateRequest $request, string $id)
    {
        $request = $request->validated();

        try {
            $jobTemplate = $this->jobTemplateRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Diubah', new JobTemplateResource($jobTemplate), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Template Job Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/job-templates/{id}",
     *     tags={"Job Templates"},
     *     summary="Delete job template",
     *     description="Delete a job template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Job Template deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job Template not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $this->jobTemplateRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Dihapus', null, 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Template Job Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/job-templates/{id}/assign-branches",
     *     tags={"Job Templates"},
     *     summary="Assign branches to job template",
     *     description="Assign one or more branches to a job template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"branches"},
     *             @OA\Property(
     *                 property="branches",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="branch_id", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Branches assigned successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/JobTemplate")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job Template not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function assignBranches(Request $request, string $id)
    {
        $request = $request->validate([
            'branches' => ['required', 'array'],
            'branches.*.branch_id' => ['required', 'exists:branches,id'],
        ]);

        try {
            $jobTemplate = $this->jobTemplateRepository->update($id, $request);

            return ResponseHelper::jsonResponse(true, 'Cabang Berhasil Ditugaskan ke Template Job', new JobTemplateResource($jobTemplate), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Template Job Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/job-templates/{id}/branches/{branchId}",
     *     tags={"Job Templates"},
     *     summary="Remove branch from job template",
     *     description="Remove a branch assignment from a job template",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="branchId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Branch removed successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/JobTemplate")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job Template or Branch not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function removeBranch(string $id, string $branchId)
    {
        try {
            $jobTemplate = $this->jobTemplateRepository->getById($id);
            $jobTemplate->branches()->detach($branchId);

            return ResponseHelper::jsonResponse(true, 'Cabang Berhasil Dihapus dari Template Job', new JobTemplateResource($jobTemplate->load(['branches'])), 200);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Data Template Job Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }
}
