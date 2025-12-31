<?php

namespace App\Repositories;

use App\Interfaces\ElectricityMeterRepositoryInterface;
use App\Models\ElectricityMeter;
use Illuminate\Support\Facades\DB;

class ElectricityMeterRepository implements ElectricityMeterRepositoryInterface
{
    /**
     * Get all electricity meters with optional filters.
     */
    public function getAll(?string $search = null, ?int $branchId = null, ?bool $isActive = null)
    {
        $query = ElectricityMeter::with(['branch'])
            ->when($search, function ($q) use ($search) {
                return $q->search($search);
            })
            ->when($branchId, function ($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->when($isActive !== null, function ($q) use ($isActive) {
                return $q->where('is_active', $isActive);
            })
            ->orderBy('branch_id')
            ->orderBy('meter_name');

        return $query->get();
    }

    /**
     * Get paginated electricity meters.
     */
    public function getAllPaginated(int $perPage = 15, ?string $search = null, ?int $branchId = null, ?bool $isActive = null)
    {
        $query = ElectricityMeter::with(['branch'])
            ->when($search, function ($q) use ($search) {
                return $q->search($search);
            })
            ->when($branchId, function ($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->when($isActive !== null, function ($q) use ($isActive) {
                return $q->where('is_active', $isActive);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get data for export.
     */
    public function getExportData(?string $search = null, ?int $branchId = null, ?bool $isActive = null)
    {
        return $this->getAll($search, $branchId, $isActive);
    }

    /**
     * Create a new electricity meter.
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return ElectricityMeter::create($data);
        });
    }

    /**
     * Find electricity meter by ID.
     */
    public function findById(int $id)
    {
        return ElectricityMeter::with(['branch'])->findOrFail($id);
    }

    /**
     * Update electricity meter.
     */
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $meter = ElectricityMeter::findOrFail($id);
            $meter->update($data);
            return $meter->fresh(['branch']);
        });
    }

    /**
     * Delete electricity meter.
     */
    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $meter = ElectricityMeter::findOrFail($id);
            return $meter->delete();
        });
    }
}
