<?php

namespace App\Repositories;

use App\Interfaces\JobTemplateRepositoryInterface;
use App\Models\JobTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobTemplateRepository implements JobTemplateRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?bool $isActive = null
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = JobTemplate::with(['branches'])
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('frequency', 'like', '%' . $search . '%');
                }
            });

        // Apply filters
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        // Role-based visibility
        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins can see all job templates
        } elseif ($user && $user->hasRole('staff')) {
            // staff can only see job templates assigned to their branch
            $query->whereHas('branches', function ($branchQuery) use ($user) {
                $branchQuery->where('branch_id', $user->branch_id);
            });

            // workReports
            $now = Carbon::now();

            // Hide job templates that already have a work report for this staff and branch in the current period
            // Only hide the specific job template that was used, not all templates with the same frequency
            $usedJobTemplateIds = DB::table('work_reports')
                ->join('job_templates', 'work_reports.job_template_id', '=', 'job_templates.id')
                ->where('work_reports.user_id', $user->id)
                ->where('work_reports.branch_id', $user->branch_id)
                ->whereNotNull('work_reports.job_template_id') // Only check templates, not custom jobs
                ->where(function ($q) use ($now) {
                    $q->orWhere(function ($q1) use ($now) {
                        // Harian: today
                        $q1->where('job_templates.frequency', 'daily')
                            ->whereDate('work_reports.created_at', $now->toDateString());
                    })
                        ->orWhere(function ($q2) use ($now) {
                            // Mingguan: this week (ISO)
                            $q2->where('job_templates.frequency', 'weekly')
                                ->whereRaw('YEARWEEK(work_reports.created_at, 1) = YEARWEEK(?, 1)', [$now]);
                        })
                        ->orWhere(function ($q3) use ($now) {
                            // Bulanan: this month
                            $q3->where('job_templates.frequency', 'monthly')
                                ->whereYear('work_reports.created_at', $now->year)
                                ->whereMonth('work_reports.created_at', $now->month);
                        });
                })
                ->pluck('work_reports.job_template_id')
                ->unique()
                ->filter() // Remove null values
                ->toArray();

            if (!empty($usedJobTemplateIds)) {
                $query->whereNotIn('id', $usedJobTemplateIds);
            }
        } else {
            // default: regular user can only see job templates assigned to their branch
            if ($user) {
                $query->whereHas('branches', function ($branchQuery) use ($user) {
                    $branchQuery->where('branch_id', $user->branch_id);
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
        ?bool $isActive = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $isActive
        );

        return $query->paginate($rowPerPage);
    }

    public function getById(
        string $id
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = JobTemplate::with(['branches'])->where('id', $id);

        // Role-based visibility
        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins can access any job template
        } elseif ($user && $user->hasRole('staff')) {
            // staff can only access job templates assigned to their branch
            $query->whereHas('branches', function ($branchQuery) use ($user) {
                $branchQuery->where('branch_id', $user->branch_id);
            });
        } else {
            // default: regular user can only access job templates assigned to their branch
            if ($user) {
                $query->whereHas('branches', function ($branchQuery) use ($user) {
                    $branchQuery->where('branch_id', $user->branch_id);
                });
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $jobTemplate = new JobTemplate();
            $jobTemplate->name = $data['name'];
            $jobTemplate->description = $data['description'];
            $jobTemplate->frequency = $data['frequency'];
            $jobTemplate->is_active = $data['is_active'] ?? true;
            $jobTemplate->save();

            // Handle branch assignments
            if (isset($data['branches']) && is_array($data['branches'])) {
                $syncData = [];
                foreach ($data['branches'] as $branch) {
                    $syncData[$branch['branch_id']] = [
                        'started_at' => now(),
                        'is_active' => true,
                    ];
                }
                $jobTemplate->branches()->sync($syncData);
            }

            return $jobTemplate->load(['branches']);
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $jobTemplate = $this->getById($id);

            $update = [
                'name' => $data['name'] ?? $jobTemplate->name,
                'description' => $data['description'] ?? $jobTemplate->description,
                'frequency' => $data['frequency'] ?? $jobTemplate->frequency,
                'is_active' => $data['is_active'] ?? $jobTemplate->is_active,
            ];

            $jobTemplate->fill($update)->save();

            // Handle branch assignments
            if (isset($data['branches']) && is_array($data['branches'])) {
                $syncData = [];
                foreach ($data['branches'] as $branch) {
                    $syncData[$branch['branch_id']] = [
                        'started_at' => now(),
                        'is_active' => true,
                    ];
                }
                $jobTemplate->branches()->sync($syncData);
            }

            return $jobTemplate->load(['branches']);
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $jobTemplate = $this->getById($id);

            $jobTemplate->delete();

            return $jobTemplate;
        });
    }
}
