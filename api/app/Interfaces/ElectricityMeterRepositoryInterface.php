<?php

namespace App\Interfaces;

interface ElectricityMeterRepositoryInterface
{
    public function getAll(?string $search = null, ?int $branchId = null, ?bool $isActive = null);

    public function getAllPaginated(int $perPage = 15, ?string $search = null, ?int $branchId = null, ?bool $isActive = null);

    public function getExportData(?string $search = null, ?int $branchId = null, ?bool $isActive = null);

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);

    public function delete(int $id);
}
