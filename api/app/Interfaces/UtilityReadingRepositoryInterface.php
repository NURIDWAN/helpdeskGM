<?php

namespace App\Interfaces;

interface UtilityReadingRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getExportData(
        ?string $search,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
}

