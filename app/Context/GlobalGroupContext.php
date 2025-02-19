<?php

namespace App\Context;

use App\Models\User;
use App\Models\Group;
use App\Models\Advice;
use Illuminate\Support\Facades\Log;

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

    public function actsAsGroupAdmin(User $user, Group $group): bool{

        if ($user->isGlobalAdmin()) {
            return true;
        }

        if($group->users()->wherePivot('user_id', $user->id)->wherePivot('is_admin', true)->exists()){
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($this->actsAsGroupAdmin($user, $ancestor)) {
                return true;
            }
        }
        return false;
    }

    public function hasAccessToGroup(User $user, Group $group): bool{
        return $user->belongsToGroup($group);
    }

    public function hasAccessToAdvice(User $user, Advice $advice): bool{

        if($advice->user_id === $user->id){
            return true;
        }

        return $this->hasAccessToGroup($user, $advice->group);
    }

    public function hasAccessToAdvisor(User $user, User $advisor): bool{
        return $advisor->groups()->wherePivot('user_id', $user->id)->exists();
    }
}