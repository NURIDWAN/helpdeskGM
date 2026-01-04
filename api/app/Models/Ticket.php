<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'code',
        'description',
        'status',
        'priority',
        'branch_id',
        'category_id',
        'completed_at',
        'unassigned_alert_sent_at',
        'notif_staff_sent',
        'notif_group_sent',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'completed_at' => 'datetime',
        'unassigned_alert_sent_at' => 'datetime',
        'notif_staff_sent' => 'boolean',
        'notif_group_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedStaff()
    {
        return $this->belongsToMany(User::class, 'ticket_staff', 'ticket_id', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class);
    }
}
