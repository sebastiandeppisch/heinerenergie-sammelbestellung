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
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @property int $group_id
 * @property Coordinate $coordinate
 * @property Carbon $created_at
 */
class MapPoint extends Model
{
    /** @use HasFactory<MapPointFactory> */
    use HasFactory;

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
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'published' => 'boolean',
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
     * The stored field values go with the point. The foreign keys do not cascade, so they are deleted here.
     */
    #[Override]
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->fields()->get()->each->delete();

            return parent::delete();
        });
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
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
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
