<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReportAttachment extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_report_id',
        'file_path',
        'file_type',
    ];

    public function workReport(): BelongsTo
    {
        return $this->belongsTo(WorkReport::class);
    }
}
