<?php

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
        return $this->groupContext->isActingAsSystemAdmin($user);
    }

    public function view(User $user, MapPointCategory $category): bool
    {
        return $this->groupContext->isActingAsSystemAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->groupContext->isActingAsSystemAdmin($user);
    }

    public function update(User $user, MapPointCategory $category): bool
    {
        return $this->groupContext->isActingAsSystemAdmin($user);
    }

    public function delete(User $user, MapPointCategory $category): bool
    {
        return $this->groupContext->isActingAsSystemAdmin($user);
    }
}
