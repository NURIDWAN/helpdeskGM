<?php

namespace App\Repositories;

use App\Interfaces\UtilityReadingRepositoryInterface;
use App\Models\UtilityReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UtilityReadingRepository implements UtilityReadingRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = UtilityReading::with(['dailyRecord.user', 'dailyRecord.branch'])
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->search($search);
                }
            });

        // Apply filters
        if ($dailyRecordId) {
            $query->where('daily_record_id', $dailyRecordId);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Role-based visibility
        if ($user && $user->can('utility-reading-view-all')) {
            // admins and superadmins can see all utility readings
        } elseif ($user && ($user->hasRole('staff') || !$user->can('utility-reading-view-all'))) {
            // staff/user can see utility readings from their branch, or their own daily records
            $query->whereHas('dailyRecord', function ($dailyRecordQuery) use ($user) {
                if ($user->branch_id) {
                    $dailyRecordQuery->where('branch_id', $user->branch_id);
                } else {
                    $dailyRecordQuery->where('user_id', $user->id);
                }
            });
        } else {
            // default fallback
            if ($user) {
                $query->whereHas('dailyRecord', function ($dailyRecordQuery) use ($user) {
                    if ($user->branch_id) {
                        $dailyRecordQuery->where('branch_id', $user->branch_id);
                    } else {
                        $dailyRecordQuery->where('user_id', $user->id);
                    }
                });
            } else {
                // no auth user, return empty
                $query->whereRaw('1=0');
            }
        }

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
        ?int $rowPerPage,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $dailyRecordId,
            $category,
            $startDate,
            $endDate
        );

        return $query->paginate($rowPerPage);
    }

    public function getById($id)
    {
        $query = UtilityReading::with(['dailyRecord.user', 'dailyRecord.branch']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && ($user->hasRole('staff') || !$user->can('utility-reading-view-all'))) {
            $query->whereHas('dailyRecord', function ($dailyRecordQuery) use ($user) {
                if ($user->branch_id) {
                    $dailyRecordQuery->where('branch_id', $user->branch_id);
                } else {
                    $dailyRecordQuery->where('user_id', $user->id);
                }
            });
        }

        return $query->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return UtilityReading::create($data);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $utilityReading = UtilityReading::findOrFail($id);
            $utilityReading->fill($data);
            $utilityReading->save();
            return $utilityReading;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $utilityReading = UtilityReading::findOrFail($id);
            $utilityReading->delete();
            return true;
        });
    }

    public function getExportData(
        ?string $search,
        ?int $dailyRecordId = null,
        ?string $category = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $dailyRecordId,
            $category,
            $startDate,
            $endDate
        );

        return $query->get();
    }
}

