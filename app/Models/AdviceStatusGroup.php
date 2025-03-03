<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdviceStatusGroup extends Pivot
{
    public $casts = [
        'visible' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function adviceStatus(): BelongsTo
    {
        return $this->belongsTo(AdviceStatus::class);
    }
}
