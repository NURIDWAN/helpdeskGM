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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'frequency' => JobTemplateFrequency::class,
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_job_templates');
    }

    public function workReports()
    {
        return $this->hasMany(WorkReport::class);
    }
}
