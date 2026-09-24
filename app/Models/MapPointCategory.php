<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\MapPointCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property int $group_id
 */
class MapPointCategory extends Model
{
    /** @use HasFactory<MapPointCategoryFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'group_id',
        'name',
        'image_path',
    ];

    /**
     * @return HasMany<MapPoint, $this>
     */
    public function mapPoints(): HasMany
    {
        return $this->hasMany(MapPoint::class, 'category_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Categories are shared down the hierarchy: a group may use its own
     * categories and the ones of all its ancestors.
     *
     * @param  Builder<MapPointCategory>  $query
     */
    #[Scope]
    protected function usableInGroup(Builder $query, Group $group): void
    {
        $query->whereIn('group_id', $group->getHierarchyIds());
    }

    public function isUsableInGroup(Group $group): bool
    {
        return in_array($this->group_id, $group->getHierarchyIds(), true);
    }

    /**
     * The map of a group shows the points of the group and its descendants. A category can
     * therefore appear on it when it belongs to the group, an ancestor or a descendant.
     */
    public function isAvailableOnMapOfGroup(Group $group): bool
    {
        return in_array($this->group_id, [...$group->getHierarchyIds(), ...$group->getSubtreeIds()], true);
    }

    #[Override]
    public function delete(): ?bool
    {
        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }

        foreach ($this->mapPoints as $mapPoint) {
            $mapPoint->update(['category_id' => null]);
        }

        return parent::delete();
    }
}
