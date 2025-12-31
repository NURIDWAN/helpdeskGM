<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectricityMeter extends Model
{
    protected $fillable = [
        'branch_id',
        'meter_name',
        'meter_number',
        'location',
        'power_capacity',
        'is_active',
    ];

    protected $casts = [
        'power_capacity' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for filtering by search term.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('meter_name', 'like', '%' . $search . '%')
                ->orWhere('meter_number', 'like', '%' . $search . '%')
                ->orWhere('location', 'like', '%' . $search . '%')
                ->orWhereHas('branch', function ($branchQuery) use ($search) {
                    $branchQuery->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Scope for filtering active meters only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the branch that owns the electricity meter.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the electricity readings for the meter.
     */
    public function readings(): HasMany
    {
        return $this->hasMany(ElectricityReading::class);
    }
}
