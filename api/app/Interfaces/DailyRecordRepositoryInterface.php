<?php

namespace App\Interfaces;

interface DailyRecordRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getExportData(
        ?string $search,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
}

