<?php

namespace App\Interfaces;

interface ElectricityReadingRepositoryInterface
{
    public function getAll(?string $search = null, ?int $dailyRecordId = null, ?int $electricityMeterId = null);

    public function getAllPaginated(int $perPage = 15, ?string $search = null, ?int $dailyRecordId = null, ?int $electricityMeterId = null);

    public function getExportData(?string $search = null, ?int $dailyRecordId = null, ?int $branchId = null, ?string $startDate = null, ?string $endDate = null);

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);

    public function delete(int $id);
}
