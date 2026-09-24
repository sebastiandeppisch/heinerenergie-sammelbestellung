<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\ValueObjects\MapPointCategoryTree;
use Database\Factories\MapPointCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property int $group_id
 * @property int|null $parent_id
 */
class MapPointCategory extends Model
{
    /** @use HasFactory<MapPointCategoryFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'group_id',
        'parent_id',
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
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'parent_id');
    }

    /**
     * @return HasMany<MapPointCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MapPointCategory::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<MapEmbed, $this>
     */
    public function mapEmbeds(): BelongsToMany
    {
        return $this->belongsToMany(MapEmbed::class);
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

    /**
     * Loads the categories of all groups as tree. That is fine because there are only few categories.
     */
    public static function tree(): MapPointCategoryTree
    {
        return MapPointCategoryTree::fromCategories(self::query()->get(['id', 'uuid', 'parent_id', 'image_path']));
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

    /**
     * Sub categories and points move up to the parent, so nothing is lost. The parent's group is
     * an ancestor of this category's group, so they may still use it. Embeds showing this category
     * keep showing its sub categories.
     */
    #[Override]
    public function delete(): ?bool
    {
        $isDeleted = DB::transaction(function (): ?bool {
            $childIds = $this->children()->pluck('id')->all();

            foreach ($this->mapEmbeds as $mapEmbed) {
                $mapEmbed->mapPointCategories()->syncWithoutDetaching($childIds);
            }

            $this->children()->update(['parent_id' => $this->parent_id]);
            $this->mapPoints()->update(['category_id' => $this->parent_id]);

            return parent::delete();
        });

        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }

        return $isDeleted;
    }
}
