<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormPermintaanItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'form_permintaan_id',
        'product_description',
        'quantity',
        'uom',
        'notes',
    ];

    public function formPermintaan(): BelongsTo
    {
        return $this->belongsTo(FormPermintaan::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FormPermintaanAttachment::class, 'form_permintaan_item_id');
    }
}
