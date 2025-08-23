<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdviceStatusGroup extends Pivot
{
    public $casts = [
        'visible' => 'boolean',
    ];

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<AdviceStatus, $this>
     */
    public function adviceStatus(): BelongsTo
    {
        return $this->belongsTo(AdviceStatus::class);
    }
}
