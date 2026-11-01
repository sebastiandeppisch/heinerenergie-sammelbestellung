<?php

declare(strict_types=1);

namespace App\HexNet\Policies;

use App\HexNet\Models\HexNet;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * A HexaNetz is seen by admins of its group, of its subgroups and of its ancestors, not by neighbouring groups.
 */
class HexNetPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function view(User $user, HexNet $hexNet): bool
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        return $this->isGroupAdmin($user)
            && $currentGroup !== null
            && in_array((int) $hexNet->group_id, array_map(intval(...), [...$currentGroup->getHierarchyIds(), ...$currentGroup->getSubtreeIds()]), true);
    }

    public function create(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function update(User $user, HexNet $hexNet): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $hexNet->group);
    }

    public function delete(User $user, HexNet $hexNet): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $hexNet->group);
    }

    private function isGroupAdmin(User $user): bool
    {
        $currentGroup = $this->groupContext->getCurrentGroup();

        return $currentGroup !== null && $this->groupContext->isActingAsTransitiveAdmin($user, $currentGroup);
    }
}
