<?php

namespace App\Models;

use App\Casts\AdviceEventCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviceEvent extends Model
{
    protected $fillable = [
        'advice_id',
        'user_id',
        'event',
    ];

    protected $appends = [
        'description',
    ];

    protected $casts = [
        'event' => AdviceEventCast::class,
    ];

    public function advice(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDescriptionAttribute(): string
    {
        return $this->event->getDescription();
    }
}
