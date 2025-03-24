<?php

namespace App\Context;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;

class GlobalGroupContext implements GroupContextContract
{
    public function getCurrentGroup(): ?Group
    {
        return null;
    }

    public function actsAsSystemAdmin(User $user): bool
    {
        return $user->isGlobalAdmin();
    }

    public function actsAsGroupAdmin(User $user, Group $group): bool
    {

        if ($user->isGlobalAdmin()) {
            return true;
        }

        if ($group->users()->wherePivot('user_id', $user->id)->wherePivot('is_admin', true)->exists()) {
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($this->actsAsGroupAdmin($user, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function hasAccessToGroup(User $user, Group $group): bool
    {
        if ($user->belongsToGroup($group)) {
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($user->belongsToGroup($ancestor)) {
                return true;
            }
        }

        foreach ($group->descendants() as $descendant) {
            if ($user->belongsToGroup($descendant)) {
                return true;
            }
        }

        return false;
    }

    public function actsAsGroupMember(User $user, Group $group): bool
    {
        return $group->users()->contains($user);
    }

    public function actsAsTransitiveGroupMember(User $user, Group $group): bool
    {
        
        foreach($group->ancestors() as $ancestor){
            if($this->actsAsGroupMember($user, $ancestor)){
                return true;
            }
        }

        return false;

    }
}
