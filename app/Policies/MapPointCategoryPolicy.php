<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MapPointCategory;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class MapPointCategoryPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function view(User $user, MapPointCategory $category): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    /**
     * Categories inherited from an ancestor group can be used, but only admins of the
     * owning group (or its ancestors) may change them.
     */
    public function update(User $user, MapPointCategory $category): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $category->group);
    }

    public function delete(User $user, MapPointCategory $category): bool
    {
        return $this->groupContext->isActingAsTransitiveAdmin($user, $category->group);
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
