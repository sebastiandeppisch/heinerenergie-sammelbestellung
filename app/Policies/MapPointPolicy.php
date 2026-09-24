<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MapPoint;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class MapPointPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function update(User $user, MapPoint $mapPoint): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $mapPoint->group);
    }

    public function delete(User $user, MapPoint $mapPoint): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $mapPoint->group);
    }

    /**
     * Imported points belong to the current group, so importing needs a selected group.
     */
    public function import(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function export(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    /**
     * System admins are already granted access via `before()`. This covers admins of the
     * currently active group (transitively, including admins of ancestor groups).
     */
    private function isGroupAdmin(User $user): bool
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        return $currentGroup !== null && $this->groupContext->isActingAsTransitiveAdmin($user, $currentGroup);
    }
}
