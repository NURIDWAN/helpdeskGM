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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(JobTemplateStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $jobTemplate = $this->jobTemplateRepository->create($request);

            return ResponseHelper::jsonResponse(true, 'Data Template Job Berhasil Ditambahkan', new JobTemplateResource($jobTemplate), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
     * Assign branches to job template.
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
     * Remove branch assignment from job template.
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
