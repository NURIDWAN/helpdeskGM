<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\WorkOrderStatus;

class WorkOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'assigned_to',
        'number',
        'description',
        'status',
        'damage_unit',
        'contact_person',
        'contact_phone',
        'product_type',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
    ];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'purchase_date' => 'date',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('number', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhereHas('ticket', function ($ticketQuery) use ($search) {
                    $ticketQuery->where('description', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                })
                ->orWhereHas('assignedUser', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Get the ticket that owns the work order.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class)->withDefault();
    }

    /**
     * Get the user assigned to this work order.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the work reports for this work order.
     */
    public function workReports(): HasMany
    {
        return $this->hasMany(WorkReport::class);
    }
}
