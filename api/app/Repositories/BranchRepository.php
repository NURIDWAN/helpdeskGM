<?php

namespace App\Repositories;

use App\Interfaces\BranchRepositoryInterface;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BranchRepository implements BranchRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute
    ) {
        $query = Branch::where(function ($query) use ($search) {
            if ($search) {
                $query->search($search);
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
    ) {
        $query = $this->getAll(
            $search,
            null,
            false
        );

        return $query->paginate($rowPerPage);
    }

    public function getById(
        string $id
    ) {
        return Branch::findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $branch = new Branch();
            $branch->name = $data['name'];
            $branch->address = $data['address'];

            // Handle logo upload
            if (isset($data['logo']) && $data['logo']) {
                $logoPath = $data['logo']->store('branches/logos', 'public');
                $branch->logo = $logoPath;
            }

            $branch->save();

            // Note: Electricity meters must be created manually by admin
            // through the branch edit page

            return $branch;
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $branch = $this->getById($id);

            // Handle logo upload
            if (isset($data['logo']) && $data['logo']) {
                // Delete old logo if exists
                if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                    Storage::disk('public')->delete($branch->logo);
                }

                // Store new logo
                $logoPath = $data['logo']->store('branches/logos', 'public');
                $data['logo'] = $logoPath;
            } else {
                // Keep existing logo if no new logo provided
                unset($data['logo']);
            }

            $branch->fill([
                'name' => $data['name'] ?? $branch->name,
                'address' => $data['address'] ?? $branch->address,
                'logo' => $data['logo'] ?? $branch->logo,
            ])->save();

            return $branch;
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $branch = $this->getById($id);

            // Delete logo file if exists
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }

            $branch->delete();

            return $branch;
        });
    }
}
