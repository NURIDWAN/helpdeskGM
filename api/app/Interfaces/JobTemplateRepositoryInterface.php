<?php

namespace App\Interfaces;

interface JobTemplateRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?bool $isActive = null
    );

    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?bool $isActive = null
    );

    public function getById(
        string $id
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
}
