<?php

namespace App\Models;

use App\Enums\UtilityCategory;
use App\Enums\UtilitySubType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityReading extends Model
{

    protected $fillable = [
        'daily_record_id',
        'category',
        'sub_type',
        'location',
        'meter_value',
        'photo',
        // Fields for Gas category
        'stove_type',
        'gas_type',
        // Fields for Electricity category (WBP and LWBP)
        'meter_value_wbp',
        'meter_value_lwbp',
        'photo_wbp',
        'photo_lwbp',
    ];

    protected $casts = [
        'category' => UtilityCategory::class,
        'sub_type' => UtilitySubType::class,
        'meter_value' => 'decimal:2',
        'meter_value_wbp' => 'decimal:2',
        'meter_value_lwbp' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('location', 'like', '%' . $search . '%')
                ->orWhere('meter_value', 'like', '%' . $search . '%')
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
     * Get the daily record that owns the utility reading.
     */
    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class, 'daily_record_id');
    }
}
