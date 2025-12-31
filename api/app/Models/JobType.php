<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobType extends Model
{
    protected $fillable = [
        'name',
    ];

    public function workReports()
    {
        return $this->hasMany(WorkReport::class);
    }
}
