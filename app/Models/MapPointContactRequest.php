<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\MapPointContactRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $map_point_id
 * @property string $sender_name
 * @property string $sender_email
 * @property string $message
 * @property string $confirmation_token_hash
 * @property Carbon $confirmation_expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $forwarded_at
 * @property Carbon $created_at
 * @property-read MapPoint $mapPoint
 */
class MapPointContactRequest extends Model
{
    /** @use HasFactory<MapPointContactRequestFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'sender_name',
        'sender_email',
        'message',
        'confirmation_token_hash',
        'confirmation_expires_at',
        'confirmed_at',
        'forwarded_at',
    ];

    protected $hidden = [
        'confirmation_token_hash',
    ];

    protected $casts = [
        'confirmation_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'forwarded_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<MapPoint, $this>
     */
    public function mapPoint(): BelongsTo
    {
        return $this->belongsTo(MapPoint::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isConfirmationExpired(): bool
    {
        return $this->confirmation_expires_at->isPast();
    }
}
