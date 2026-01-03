<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\WorkReportStatus;

class WorkReport extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'work_order_id',
        'job_template_id',
        'description',
        'custom_job',
        'status',
    ];

    protected $casts = [
        'status' => WorkReportStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', '%' . $search . '%')
                ->orWhere('custom_job', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('branch', function ($branchQuery) use ($search) {
                    $branchQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('workOrder', function ($workOrderQuery) use ($search) {
                    $workOrderQuery->where('number', 'like', '%' . $search . '%');
                })
                ->orWhereHas('jobTemplate', function ($jobTemplateQuery) use ($search) {
                    $jobTemplateQuery->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Get the user that owns the work report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the work order that owns the work report.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the branch that owns the work report.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the job template for the work report.
     */
    public function jobTemplate(): BelongsTo
    {
        return $this->belongsTo(JobTemplate::class);
    }

    /**
     * Get the attachments for the work report.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkReportAttachment::class);
    }
}
