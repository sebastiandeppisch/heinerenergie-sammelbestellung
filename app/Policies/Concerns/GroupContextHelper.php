<?php

namespace App\Policies\Concerns;

use App\Context\GroupContextContract;
use App\Models\User;

trait GroupContextHelper
{
    public function __construct(private GroupContextContract $groupContext) {}

    public function before(User $user): ?bool
    {
        if ($this->groupContext->isActingAsSystemAdmin($user)) {
            return true;
        }

        return null;
    }
}
