<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormPermintaanAttachment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'form_permintaan_id',
        'form_permintaan_item_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function formPermintaan(): BelongsTo
    {
        return $this->belongsTo(FormPermintaan::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FormPermintaanItem::class, 'form_permintaan_item_id');
    }
}
