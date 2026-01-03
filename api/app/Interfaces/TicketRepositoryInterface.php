<?php

namespace App\Interfaces;

interface TicketRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?string $status = null,
        ?string $priority = null,
        ?int $branchId = null,
        ?int $assignedTo = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $categoryId = null
    );

    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?string $status = null,
        ?string $priority = null,
        ?int $branchId = null,
        ?int $assignedTo = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $categoryId = null
    );

    public function getById(
        string $id
    );

    public function getByCode(
        string $code
    );

    public function create(
        array $data
    );

    public function update(
        string $id,
        array $data
    );

    public function delete(
        string $id
    );

    public function assignStaff(
        string $id,
        array $staffIds
    );
}
