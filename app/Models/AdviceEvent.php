<?php

namespace App\Models;

use App\Casts\AdviceEventCast;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviceEvent extends Model
{
    use HasUuid;

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

    /**
     * @return BelongsTo<Advice, $this>
     */
    public function advice(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDescriptionAttribute(): string
    {
        return $this->event->getDescription();
    }
}
