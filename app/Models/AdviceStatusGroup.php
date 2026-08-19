<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdviceStatusGroup extends Pivot
{
    public $casts = [
        'visible' => 'boolean',
        'visible_in_group' => 'boolean',
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
