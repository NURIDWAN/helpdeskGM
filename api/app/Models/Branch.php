<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'address',
        'logo',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function workReports()
    {
        return $this->hasMany(WorkReport::class);
    }

    public function jobTemplates()
    {
        return $this->belongsToMany(JobTemplate::class, 'branch_job_templates');
    }

    public function dailyRecords()
    {
        return $this->hasMany(DailyRecord::class);
    }

    /**
     * Get the electricity meters for the branch.
     */
    public function electricityMeters(): HasMany
    {
        return $this->hasMany(ElectricityMeter::class);
    }

    /**
     * Get all users belonging to this branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all staff members belonging to this branch.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->role('staff');
    }
}
