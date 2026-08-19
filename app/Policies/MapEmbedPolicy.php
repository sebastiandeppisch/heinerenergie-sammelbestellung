<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MapEmbed;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class MapEmbedPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function view(User $user, MapEmbed $mapEmbed): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function update(User $user, MapEmbed $mapEmbed): bool
    {
        return $this->isGroupAdmin($user);
    }

    public function delete(User $user, MapEmbed $mapEmbed): bool
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
