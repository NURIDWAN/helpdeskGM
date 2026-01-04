<?php

namespace App\Models;

use App\Enums\JobTemplateFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'frequency',
        'is_active',
        'schedule_details',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'frequency' => JobTemplateFrequency::class,
        'schedule_details' => 'array',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_job_templates')
            ->withPivot(['is_active', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function workReports()
    {
        return $this->hasMany(WorkReport::class);
    }
}
