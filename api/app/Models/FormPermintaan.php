<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormPermintaan extends Model
{
    use LogsActivity;

    protected $table = 'form_permintaan';

    protected $fillable = [
        'user_id',
        'branch_id',
        'ticket_id',
        'request_number',
        'date',
        'priority',
        'request_type',
        'fa_number',
        'reason',
        'status',
        'confirmed_by',
        'confirmed_at',
        'reviewed_by',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FormPermintaanItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FormPermintaanAttachment::class);
    }
}
