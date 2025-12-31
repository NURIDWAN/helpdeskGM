<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricityReading extends Model
{
    protected $fillable = [
        'daily_record_id',
        'electricity_meter_id',
        'meter_value_wbp',
        'meter_value_lwbp',
        'photo_wbp',
        'photo_lwbp',
    ];

    protected $casts = [
        'meter_value_wbp' => 'decimal:2',
        'meter_value_lwbp' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for filtering by search term.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('electricityMeter', function ($meterQuery) use ($search) {
                $meterQuery->where('meter_name', 'like', '%' . $search . '%')
                    ->orWhere('meter_number', 'like', '%' . $search . '%');
            })
                ->orWhereHas('dailyRecord', function ($dailyRecordQuery) use ($search) {
                    $dailyRecordQuery->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    })
                        ->orWhereHas('branch', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
        });
    }

    /**
     * Get the daily record that owns the electricity reading.
     */
    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    /**
     * Get the electricity meter that owns this reading.
     */
    public function electricityMeter(): BelongsTo
    {
        return $this->belongsTo(ElectricityMeter::class);
    }

    /**
     * Calculate total usage (WBP + LWBP).
     */
    public function getTotalUsageAttribute(): ?float
    {
        if ($this->meter_value_wbp === null && $this->meter_value_lwbp === null) {
            return null;
        }

        return round(($this->meter_value_wbp ?? 0) + ($this->meter_value_lwbp ?? 0), 2);
    }
}
