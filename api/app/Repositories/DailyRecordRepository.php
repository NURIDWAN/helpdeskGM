<?php

namespace App\Repositories;

use App\Interfaces\DailyRecordRepositoryInterface;
use App\Models\DailyRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyRecordRepository implements DailyRecordRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = DailyRecord::with(['user', 'branch', 'utilityReadings', 'electricityReadings.electricityMeter'])
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->search($search);
                }
            });

        // Apply filters
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Role-based visibility
        if ($user && $user->hasRole('admin')) {
            // admins can see all daily records
        } elseif ($user && $user->hasRole('staff')) {
            // staff can only see their own daily records
            $query->where('user_id', $user->id);
        } else {
            // default: regular user can only see own daily records
            if ($user) {
                $query->where('user_id', $user->id);
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
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $userId,
            $branchId,
            $startDate,
            $endDate
        );

        return $query->paginate($rowPerPage);
    }

    public function getById($id)
    {
        $query = DailyRecord::with(['user', 'branch', 'utilityReadings', 'electricityReadings.electricityMeter']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && $user->hasRole('staff')) {
            $query->where('user_id', $user->id);
        }

        return $query->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Jika user_id tidak ada di data, default ke user yang login
            if (!isset($data['user_id'])) {
                $data['user_id'] = Auth::id();
            }
            return DailyRecord::create($data);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $dailyRecord = DailyRecord::findOrFail($id);
            $dailyRecord->fill($data);
            $dailyRecord->save();
            return $dailyRecord;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $dailyRecord = DailyRecord::findOrFail($id);
            $dailyRecord->delete();
            return true;
        });
    }

    public function getExportData(
        ?string $search,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $userId,
            $branchId,
            $startDate,
            $endDate
        );

        return $query->get();
    }
}
