<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Models\Traits\HasUuid;
use App\ValueObjects\Coordinate;
use Database\Factories\MapPointFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Wnx\Sends\Contracts\HasSends;
use Wnx\Sends\Support\HasSendsTrait;

/**
 * @property int $group_id
 * @property Coordinate $coordinate
 * @property Carbon $created_at
 * @property string|null $contact_email
 * @property Carbon|null $contact_email_verified_at
 * @property bool $contact_enabled
 * @property string|null $contact_verification_token_hash
 * @property Carbon|null $contact_verification_expires_at
 * @property string|null $edit_token_hash
 * @property Carbon|null $edit_token_expires_at
 */
class MapPoint extends Model implements HasSends
{
    /** @use HasFactory<MapPointFactory> */
    use HasFactory;

    use HasSendsTrait;
    use HasUuid;

    protected $fillable = [
        'group_id',
        'title',
        'description',
        'lng',
        'lat',
        'coordinate',
        'published',
        'category_id',
        'location',
        'contact_email',
        'contact_email_verified_at',
        'contact_enabled',
    ];

    protected $hidden = [
        'contact_email',
        'contact_email_verified_at',
        'contact_verification_token_hash',
        'contact_verification_expires_at',
        'edit_token_hash',
        'edit_token_expires_at',
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'published' => 'boolean',
        'contact_enabled' => 'boolean',
        'contact_email_verified_at' => 'datetime',
        'contact_verification_expires_at' => 'datetime',
        'edit_token_expires_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Pointable<Model>&Model, $this>
     */
    public function pointable(): MorphTo
    {
        // @phpstan-ignore-next-line
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return HasMany<MapPointContactRequest, $this>
     */
    public function contactRequests(): HasMany
    {
        return $this->hasMany(MapPointContactRequest::class);
    }

    /**
     * Visitors of the public map may only contact a point whose owner opted in with a confirmed address.
     */
    public function hasContact(): bool
    {
        return $this->published
            && $this->contact_enabled
            && $this->contact_email !== null
            && $this->contact_email_verified_at !== null;
    }

    /**
     * A group sees the points of its own and of all descendant groups.
     *
     * @param  Builder<MapPoint>  $query
     */
    #[Scope]
    protected function visibleFromGroup(Builder $query, Group $group): void
    {
        $query->whereIn('group_id', $group->getSubtreeIds());
    }
}
