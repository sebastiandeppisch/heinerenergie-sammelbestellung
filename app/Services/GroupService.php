<?php

namespace App\Services;

use App\Models\Advice;
use App\Models\Group;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Collection;

class GroupService
{
    /**
     * Find a group whose consulting area contains the specified coordinates
     */
    public function findGroupContainingCoordinates(Coordinate $coordinate): ?Group
    {
        return Group::whereNotNull('consulting_area')
            ->get()
            ->filter(function ($group) use ($coordinate) {
                return $group->consulting_area->containsPoint($coordinate);
            })
            ->first();
    }

    /**
     * Find the nearest main group (parent_id = null)
     */
    public function findNearestMainGroup(Coordinate $coordinate): ?Group
    {
        $mainGroups = Group::whereNull('parent_id')->get();

        if ($mainGroups->isEmpty()) {
            return null;
        }

        $sortedgroups = $mainGroups->sortBy(function ($group) use ($coordinate) {
            $groupCenter = $group->consulting_area?->getCenter();

            if (! $groupCenter) {
                return PHP_FLOAT_MAX;
            }

            return $coordinate->distanceTo($groupCenter);
        });

        return $sortedgroups->first();
    }

    /**
     * Assign an advice to a group
     */
    public function assignAdviceToGroup(Advice $advice, Group $group): void
    {
        $advice->group_id = $group->id;
        $advice->save();
    }

    /**
     * Check if a group is a main group (parent_id = null)
     */
    public function isMainGroup(Group $group): bool
    {
        return $group->parent_id === null;
    }

    /**
     * Get all main groups
     */
    public function getAllMainGroups(): Collection
    {
        return Group::whereNull('parent_id')->get();
    }

    /**
     * Get all subgroups of a main group
     */
    public function getSubgroups(Group $mainGroup): Collection
    {
        if (! $this->isMainGroup($mainGroup)) {
            return Collection::make();
        }

        return Group::where('parent_id', $mainGroup->id)->get();
    }

    /**
     * Calculate the distance between two groups (based on polygon centers)
     */
    public function getDistanceBetweenGroups(Group $group1, Group $group2): ?float
    {
        $center1 = $group1->consulting_area?->getCenter();
        $center2 = $group2->consulting_area?->getCenter();

        if (! $center1 || ! $center2) {
            return null;
        }

        return $center1->distanceTo($center2);
    }

    /**
     * Find all groups whose consulting areas overlap
     */
    public function findOverlappingGroups(): array
    {
        $groups = Group::whereNotNull('consulting_area')->get();
        $result = [];

        foreach ($groups as $group1) {
            foreach ($groups as $group2) {
                if ($group1->id >= $group2->id) {
                    continue; // Avoid duplicates and self-comparisons
                }

                // Here a function would need to be implemented to check polygon overlap
                // Since this is not available, just a placeholder:
                // if ($this->doPolygonsOverlap($group1->consulting_area, $group2->consulting_area)) {
                //     $result[] = [$group1, $group2];
                // }
            }
        }

        return $result;
    }
}
