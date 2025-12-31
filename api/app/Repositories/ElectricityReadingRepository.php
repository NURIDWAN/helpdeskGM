<?php

namespace App\Repositories;

use App\Interfaces\ElectricityReadingRepositoryInterface;
use App\Models\ElectricityReading;
use Illuminate\Support\Facades\DB;

class ElectricityReadingRepository implements ElectricityReadingRepositoryInterface
{
    /**
     * Get all electricity readings with optional filters.
     */
    public function getAll(?string $search = null, ?int $dailyRecordId = null, ?int $electricityMeterId = null)
    {
        $query = ElectricityReading::with(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])
            ->when($search, function ($q) use ($search) {
                return $q->search($search);
            })
            ->when($dailyRecordId, function ($q) use ($dailyRecordId) {
                return $q->where('daily_record_id', $dailyRecordId);
            })
            ->when($electricityMeterId, function ($q) use ($electricityMeterId) {
                return $q->where('electricity_meter_id', $electricityMeterId);
            })
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Get paginated electricity readings.
     */
    public function getAllPaginated(int $perPage = 15, ?string $search = null, ?int $dailyRecordId = null, ?int $electricityMeterId = null)
    {
        $query = ElectricityReading::with(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])
            ->when($search, function ($q) use ($search) {
                return $q->search($search);
            })
            ->when($dailyRecordId, function ($q) use ($dailyRecordId) {
                return $q->where('daily_record_id', $dailyRecordId);
            })
            ->when($electricityMeterId, function ($q) use ($electricityMeterId) {
                return $q->where('electricity_meter_id', $electricityMeterId);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get data for export with filters.
     */
    public function getExportData(?string $search = null, ?int $dailyRecordId = null, ?int $branchId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = ElectricityReading::with(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])
            ->when($search, function ($q) use ($search) {
                return $q->search($search);
            })
            ->when($dailyRecordId, function ($q) use ($dailyRecordId) {
                return $q->where('daily_record_id', $dailyRecordId);
            })
            ->when($branchId, function ($q) use ($branchId) {
                return $q->whereHas('dailyRecord', function ($drQuery) use ($branchId) {
                    $drQuery->where('branch_id', $branchId);
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                return $q->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                return $q->whereDate('created_at', '<=', $endDate);
            })
            ->orderBy('created_at', 'asc');

        return $query->get();
    }

    /**
     * Create a new electricity reading.
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return ElectricityReading::create($data);
        });
    }

    /**
     * Find electricity reading by ID.
     */
    public function findById(int $id)
    {
        return ElectricityReading::with(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter'])->findOrFail($id);
    }

    /**
     * Update electricity reading.
     */
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $reading = ElectricityReading::findOrFail($id);
            $reading->update($data);
            return $reading->fresh(['dailyRecord.user', 'dailyRecord.branch', 'electricityMeter']);
        });
    }

    /**
     * Delete electricity reading.
     */
    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $reading = ElectricityReading::findOrFail($id);
            return $reading->delete();
        });
    }
}
