<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRecord extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'total_customers',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', '%' . $search . '%');
            })
                ->orWhereHas('branch', function ($branchQuery) use ($search) {
                    $branchQuery->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Get the user that owns the daily record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch that owns the daily record.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the utility readings for the daily record.
     */
    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class);
    }

    /**
     * Get the electricity readings for the daily record (multi-meter).
     */
    public function electricityReadings()
    {
        return $this->hasMany(ElectricityReading::class);
    }
}
