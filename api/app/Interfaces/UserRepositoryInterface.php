<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\User;

interface UserRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?array $roles,
        ?int $limit,
        bool $execute
    ): Collection|Builder;

    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage
    ): LengthAwarePaginator;

    public function getById(
        string $id
    ): User;

    public function create(
        array $data
    ): User;

    public function update(
        string $id,
        array $data
    ): User;

    public function delete(
        string $id
    ): User;
}
