<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Enums\PublicConsent;
use App\Models\Traits\HasUuid;
use App\Services\ImageStorage;
use App\ValueObjects\Coordinate;
use Database\Factories\MapPointFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Override;
use Wnx\Sends\Contracts\HasSends;
use Wnx\Sends\Support\HasSendsTrait;

/**
 * @property int $group_id
 * @property string $uuid
 * @property bool $published
 * @property PublicConsent $public_consent
 * @property Carbon|null $public_consent_at
 * @property string|null $public_consent_text
 * @property array<int, string>|null $public_consent_fields
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
        'public_consent',
        'public_consent_at',
        'public_consent_text',
        'public_consent_fields',
    ];

    protected $hidden = [
        'contact_email',
        'contact_email_verified_at',
        'contact_verification_token_hash',
        'contact_verification_expires_at',
        'edit_token_hash',
        'edit_token_expires_at',
    ];

    protected $attributes = [
        'public_consent' => 'not_required',
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'published' => 'boolean',
        'contact_enabled' => 'boolean',
        'contact_email_verified_at' => 'datetime',
        'contact_verification_expires_at' => 'datetime',
        'public_consent' => PublicConsent::class,
        'public_consent_at' => 'datetime',
        'public_consent_fields' => 'array',
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
     * The stored field values and characteristics go with the point. The foreign keys do not cascade, so they are deleted here.
     * The images of all values, including former ones, lie in the point's directory.
     */
    #[Override]
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->fields()->get()->each->delete();
            $this->characteristics()->detach();
            DB::afterCommit(fn () => app(ImageStorage::class)->deleteDirectory($this->imageDirectory()));

            return parent::delete();
        });
    }

    public function imageDirectory(): string
    {
        return 'map-point-images/'.$this->uuid;
    }

    /**
     * The stored values of category fields, including values of fields the point no longer has.
     *
     * @return HasMany<MapPointField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(MapPointField::class);
    }

    /**
     * Only characteristics that are selectable for the point's category, see MapPointCharacteristic::selectableFor().
     *
     * @return BelongsToMany<MapPointCharacteristic, $this>
     */
    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(MapPointCharacteristic::class, 'map_point_characteristic_map_point')->withTimestamps()->orderBy('sort_order');
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
            && $this->public_consent !== PublicConsent::REFUSED
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
