<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves which map points, categories and groups are visible from the current group context.
 *
 * Points are visible to their own group and all ancestor groups. Categories are shared downwards:
 * a group may use its own categories and the ones of its ancestors. Without a current group
 * (global context, system admins only) everything is visible.
 */
class MapPointVisibilityService
{
    public function __construct(private readonly GroupContextContract $groupContext) {}

    /**
     * @return Builder<MapPoint>
     */
    public function visiblePoints(): Builder
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        if ($currentGroup === null) {
            return MapPoint::query();
        }

        return MapPoint::query()->visibleFromGroup($currentGroup);
    }

    /**
     * Categories the current group may use: its own and those of its ancestors.
     *
     * @return Builder<MapPointCategory>
     */
    public function usableCategories(): Builder
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        if ($currentGroup === null) {
            return MapPointCategory::query();
        }

        return MapPointCategory::query()->usableInGroup($currentGroup);
    }

    /**
     * Categories that can appear on visible points or be chosen for a selectable group:
     * those of the current group, its ancestors and its descendants.
     *
     * @return Builder<MapPointCategory>
     */
    public function relevantCategories(): Builder
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        if ($currentGroup === null) {
            return MapPointCategory::query();
        }

        $groupIds = array_unique([...$currentGroup->getHierarchyIds(), ...$currentGroup->getSubtreeIds()]);

        return MapPointCategory::query()->whereIn('group_id', $groupIds);
    }

    /**
     * Groups that points, categories and embeds can be assigned to from the current context.
     *
     * @return Collection<int, Group>
     */
    public function selectableGroups(): Collection
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        if ($currentGroup === null) {
            return Group::all();
        }

        return new Collection([$currentGroup, ...$currentGroup->descendants()->all()]);
    }

    public function isSelectableGroup(User $user, Group $group): bool
    {
        return $this->groupContext->isActingAsSystemAdmin($user)
            || $this->groupContext->isActingAsTransitiveAdmin($user, $group);
    }

    /**
     * Maps each group uuid to the uuids of the given categories that are usable in that group,
     * so forms can offer only valid categories for the selected group.
     *
     * @param  Collection<int, Group>  $groups
     * @param  Collection<int, MapPointCategory>  $categories
     * @return array<string, array<int, string>>
     */
    public function usableCategoryIdsByGroup(Collection $groups, Collection $categories): array
    {
        $parentIdsByGroupId = $this->parentIdsByGroupId();

        return $this->categoryIdsByGroup(
            $groups,
            $categories,
            fn (Group $group, MapPointCategory $category): bool => in_array($category->group_id, $this->hierarchyIds($group->id, $parentIdsByGroupId), true),
        );
    }

    /**
     * Maps each group uuid to the uuids of the given categories that can appear on a map of that group:
     * categories of the group, its ancestors and its descendants.
     *
     * @param  Collection<int, Group>  $groups
     * @param  Collection<int, MapPointCategory>  $categories
     * @return array<string, array<int, string>>
     */
    public function mapCategoryIdsByGroup(Collection $groups, Collection $categories): array
    {
        $parentIdsByGroupId = $this->parentIdsByGroupId();

        return $this->categoryIdsByGroup(
            $groups,
            $categories,
            fn (Group $group, MapPointCategory $category): bool => in_array($category->group_id, $this->hierarchyIds($group->id, $parentIdsByGroupId), true)
                || in_array($group->id, $this->hierarchyIds($category->group_id, $parentIdsByGroupId), true),
        );
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @param  Collection<int, MapPointCategory>  $categories
     * @param  callable(Group, MapPointCategory): bool  $isAvailable
     * @return array<string, array<int, string>>
     */
    private function categoryIdsByGroup(Collection $groups, Collection $categories, callable $isAvailable): array
    {
        $categoryIdsByGroup = [];

        foreach ($groups as $group) {
            $categoryIdsByGroup[$group->uuid] = $categories
                ->filter(fn (MapPointCategory $category): bool => $isAvailable($group, $category))
                ->map(fn (MapPointCategory $category): string => $category->uuid)
                ->values()
                ->all();
        }

        return $categoryIdsByGroup;
    }

    /**
     * @return array<int, int|null>
     */
    private function parentIdsByGroupId(): array
    {
        /** @var array<int, int|null> $parentIdsByGroupId */
        $parentIdsByGroupId = Group::query()->pluck('parent_id', 'id')->all();

        return $parentIdsByGroupId;
    }

    /**
     * Walks up the group tree in memory to avoid one query per ancestor.
     *
     * @param  array<int, int|null>  $parentIdsByGroupId
     * @return array<int, int>
     */
    private function hierarchyIds(int $groupId, array $parentIdsByGroupId): array
    {
        $hierarchyIds = [];
        $currentId = $groupId;

        while ($currentId !== null && ! in_array($currentId, $hierarchyIds, true)) {
            $hierarchyIds[] = $currentId;
            $currentId = $parentIdsByGroupId[$currentId] ?? null;
        }

        return $hierarchyIds;
    }
}
