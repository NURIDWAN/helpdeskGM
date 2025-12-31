<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?array $roles,
        ?int $limit,
        bool $execute
    ): Collection|Builder {
        $query = User::where(function ($query) use ($search) {
            if ($search) {
                $query->search($search);
            }
        })->with('branch', 'roles')->whereHas('roles', function ($query) use ($roles) {
            if ($roles) {
                $query->whereIn('name', $roles);
            }
        });

        if ($limit) {
            $query->take($limit);
        }

        if ($execute) {
            return $query->get();
        }

        return $query;
    }

    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage
    ): LengthAwarePaginator {
        $query = $this->getAll(
            $search,
            null,
            null,
            false
        );

        return $query->paginate($rowPerPage);
    }

    public function getById(
        string $id
    ): User {
        return User::with('branch', 'roles')->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $data['roles'];
            unset($data['roles']);

            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);

            $user->assignRole($roles);

            return $user;
        });
    }

    public function update(string $id, array $data): User
    {
        $user = $this->getById($id);

        return DB::transaction(function () use ($user, $data) {
            if (!empty($data['roles'])) {
                $roles = $data['roles'];
                unset($data['roles']);
                $user->syncRoles($roles);
            }

            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            $user->update($data);

            return $user->fresh();
        });
    }

    public function delete(string $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = $this->getById($id);

            $user->delete();

            return $user;
        });
    }
}
