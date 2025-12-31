<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Interfaces\ElectricityMeterRepositoryInterface;
use App\Http\Requests\ElectricityMeterStoreRequest;
use App\Http\Requests\ElectricityMeterUpdateRequest;
use App\Http\Resources\ElectricityMeterResource;
use App\Http\Resources\PaginateResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ElectricityMeterController extends Controller implements HasMiddleware
{
    public function __construct(
        private ElectricityMeterRepositoryInterface $electricityMeterRepository,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('electricity-meter-list'), only: ['index', 'show', 'getAllPaginated', 'getByBranch']),
            new Middleware(PermissionMiddleware::using('electricity-meter-create'), only: ['store']),
            new Middleware(PermissionMiddleware::using('electricity-meter-edit'), only: ['update']),
            new Middleware(PermissionMiddleware::using('electricity-meter-delete'), only: ['destroy']),
        ];
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $electricityMeters = $this->electricityMeterRepository->getAll(
                $request->search,
                $request->branch_id ? (int) $request->branch_id : null,
                $request->is_active !== null ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : null
            );

            return ResponseHelper::jsonResponse(true, 'Data Meter Listrik Berhasil Diambil', ElectricityMeterResource::collection($electricityMeters), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all paginated electricity meters.
     */
    public function getAllPaginated(Request $request)
    {
        try {
            $electricityMeters = $this->electricityMeterRepository->getAllPaginated(
                $request->per_page ?? 15,
                $request->search,
                $request->branch_id ? (int) $request->branch_id : null,
                $request->is_active !== null ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : null
            );

            return ResponseHelper::jsonResponse(true, 'Data Meter Listrik Berhasil Diambil', PaginateResource::make($electricityMeters, ElectricityMeterResource::class), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ElectricityMeterStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $electricityMeter = $this->electricityMeterRepository->create($data);

            return ResponseHelper::jsonResponse(true, 'Meter Listrik Berhasil Dibuat', new ElectricityMeterResource($electricityMeter->load('branch')), 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $electricityMeter = $this->electricityMeterRepository->findById($id);

            return ResponseHelper::jsonResponse(true, 'Data Meter Listrik Berhasil Diambil', new ElectricityMeterResource($electricityMeter), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ElectricityMeterUpdateRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $electricityMeter = $this->electricityMeterRepository->update($id, $data);

            return ResponseHelper::jsonResponse(true, 'Meter Listrik Berhasil Diubah', new ElectricityMeterResource($electricityMeter), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->electricityMeterRepository->delete($id);

            return ResponseHelper::jsonResponse(true, 'Meter Listrik Berhasil Dihapus', null, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get meters by branch for dropdown selection.
     */
    public function getByBranch(Request $request, string $branchId)
    {
        try {
            $electricityMeters = $this->electricityMeterRepository->getAll(
                null,
                (int) $branchId,
                true // Only active meters
            );

            return ResponseHelper::jsonResponse(true, 'Data Meter Listrik Berhasil Diambil', ElectricityMeterResource::collection($electricityMeters), 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
        }
    }
}
