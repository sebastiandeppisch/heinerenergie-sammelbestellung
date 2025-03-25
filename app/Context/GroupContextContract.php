<?php

namespace App\Context;

use App\Models\Group;
use App\Models\User;

interface GroupContextContract
{
    public function getCurrentGroup(): ?Group;

    public function isActingAsSystemAdmin(User $user): bool;

    // is acting as a group admin methods
    public function isActingAsDirectAdmin(User $user, Group $group): bool;

    public function isActingAsTransitiveAdmin(User $user, Group $group): bool;

    public function isActingAsAncestorAdmin(User $user, Group $group): bool;

    // is acting as a group member methods

    public function isActingAsDirectMember(User $user, Group $group): bool;

    public function isActingAsTransitiveMember(User $user, Group $group): bool;

    public function isActingAsAncestorMember(User $user, Group $group): bool;

    // is acting as a group member or admin

    public function isActingAsDirectMemberOrAdmin(User $user, Group $group): bool;

    public function isActingAsTransitiveMemberOrAdmin(User $user, Group $group): bool;

    public function isActingAsAncestorMemberOrAdmin(User $user, Group $group): bool;
}
