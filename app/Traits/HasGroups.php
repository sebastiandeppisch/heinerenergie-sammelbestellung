<?php

namespace App\Traits;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

trait HasGroups
{
    /**
     * Get all groups this user belongs to
     *
     * @return BelongsToMany<Group, $this, Pivot>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * Get all groups where this user is an admin
     *
     * @return BelongsToMany<Group, $this, Pivot>
     */
    public function administeredGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->wherePivot('is_admin', true)
            ->withTimestamps();
    }

    /**
     * Check if user is a global admin
     */
    public function isGlobalAdmin(): bool
    {
        $user = User::findOrFail($this->id);

        return $user->is_admin === true;
    }

    /**
     * Check if user belongs to the given group
     * Note: Group membership is NOT inherited downwards
     */
    public function belongsToGroup(Group $group): bool
    {
        return $this->groups()->where('group_id', $group->id)->exists();
    }

    /**
     * Get all groups this user can administer (including child groups)
     * Note: Admin rights ARE inherited downwards
     */
    public function getAllAdministrableGroups(): Collection
    {
        if ($this->isGlobalAdmin()) {
            return Group::all();
        }

        $administeredGroups = $this->administeredGroups()->get();
        $allGroups = new Collection;

        foreach ($administeredGroups as $group) {
            $allGroups->push($group);
            $descendants = $group->descendants();
            $allGroups = $allGroups->merge($descendants);
        }

        return $allGroups->unique('id');
    }

    /**
     * Check if user is an admin of the given group or any of its ancestors
     * Note: Admin rights ARE inherited downwards
     */
    public function isGroupAdmin(Group $group): bool
    {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        // Check if user is admin of the given group
        if ($this->administeredGroups()->where('group_id', $group->id)->exists()) {
            return true;
        }

        // Check if user is admin of any ancestor group
        foreach ($group->ancestors() as $ancestor) {
            if ($this->administeredGroups()->where('group_id', $ancestor->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
