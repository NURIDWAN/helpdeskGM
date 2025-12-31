<?php

namespace App\Interfaces;

interface WorkReportRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?string $status = null,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $jobTemplateId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?string $status = null,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $jobTemplateId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getExportData(
        ?string $search,
        ?string $status = null,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $jobTemplateId = null,
        ?string $startDate = null,
        ?string $endDate = null
    );
}
