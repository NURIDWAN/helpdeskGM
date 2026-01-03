<?php

namespace App\Repositories;

use App\Interfaces\WorkReportRepositoryInterface;
use App\Models\WorkReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkReportRepository implements WorkReportRepositoryInterface
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
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = WorkReport::with(['user', 'branch', 'jobTemplate', 'workOrder'])
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->search($search);
                }
            });

        // Apply filters
        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($jobTemplateId) {
            $query->where('job_template_id', $jobTemplateId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Role-based visibility
        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins and superadmins can see all work reports
        } elseif ($user && $user->hasRole('staff')) {
            // staff can only see their own work reports
            $query->where('user_id', $user->id);
        } else {
            // default: regular user can only see own work reports
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
        ?string $status = null,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $jobTemplateId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $status,
            $branchId,
            $userId,
            $jobTemplateId,
            $startDate,
            $endDate
        );

        return $query->paginate($rowPerPage);
    }

    public function getById($id)
    {
        $query = WorkReport::with(['user', 'branch', 'jobTemplate', 'workOrder']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && !$user->hasRole('admin') && !$user->hasRole('superadmin') && $user->hasRole('staff')) {
            $query->where('user_id', $user->id);
        }

        return $query->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = Auth::id();
            $workReport = WorkReport::create($data);

            // Send WhatsApp Notifications
            try {
                $whatsappService = app(\App\Services\WhatsAppNotificationService::class);

                // 1. Send Report Notification to Group/Admin
                $whatsappService->sendWorkReportNotification($workReport);

                // Removed auto-complete and completion notification logic as per request
            } catch (\Exception $e) {
                // Log but don't fail
                \Illuminate\Support\Facades\Log::error('Failed to send work report notifications', [
                    'report_id' => $workReport->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $workReport;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $workReport = WorkReport::findOrFail($id);
            $workReport->fill($data);
            $workReport->save();
            return $workReport;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $workReport = WorkReport::findOrFail($id);
            $workReport->delete();
            return true;
        });
    }

    public function getExportData(
        ?string $search,
        ?string $status = null,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $jobTemplateId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $status,
            $branchId,
            $userId,
            $jobTemplateId,
            $startDate,
            $endDate
        );

        return $query->get();
    }
}
