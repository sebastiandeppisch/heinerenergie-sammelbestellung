<?php

namespace App\Services;

use App\Models\Advice;
use App\Models\User;

class AdviceService
{
    public function getDistance(Advice $advice, ?User $user = null): ?float
    {
        if ($user === null) {
            return null;
        }

        return $this->getDistanceBetween($advice, $user);
    }

    private function getDistanceBetween(Advice $advice, User $user): ?float
    {
        if($advice->coordinate === null || $user->coordinate === null) {
            return null;
        }
        return $advice->coordinate->distanceTo($user->coordinate);
    }

    public function canEdit(Advice $advice, User $user): bool
    {
        return $user->can('view', $advice);
    }
} 