<?php

namespace App\Services;

use App\Data\DataProtectedAdviceData;
use App\Events\Advice\AdviceSharedAdvisorAdded;
use App\Events\Advice\AdviceSharedAdvisorRemoved;
use App\Models\Advice;
use App\Models\User;
use App\ValueObjects\Meter;
use Illuminate\Support\Collection;

class AdviceService
{
    public function getAdvicesListForUser(User $user): Collection
    {
        return Advice::query()
            ->with('status', 'group', 'group.parent', 'group.users', 'shares', 'advisor')->get()
            ->filter(fn (Advice $advice) => $user->can('viewDataProtected', $advice))->values()->map(fn ($advice) => DataProtectedAdviceData::fromModel($advice, $user));
    }

    public function getDistance(Advice $advice, ?User $user = null): ?Meter
    {
        if ($user === null) {
            return null;
        }

        return $this->getDistanceBetween($advice, $user);
    }

    private function getDistanceBetween(Advice $advice, User $user): ?Meter
    {
        if ($advice->coordinate === null || $user->coordinate === null) {
            return null;
        }

        return $advice->coordinate->distanceTo($user->coordinate);
    }

    public function canEdit(Advice $advice, User $user): bool
    {
        return $user->can('view', $advice);
    }

    /**
     * @param  Collection<User>  $newAdvisors
     */
    public function syncShares(Advice $advice, Collection $newAdvisors, ?User $user): void
    {

        $newAdvisors = $newAdvisors->map(function (mixed $user): User {
            if (! $user instanceof User) {
                return User::findOrFail($user);
            }

            return $user;
        });

        // Get current advisors before sync
        $currentAdvisors = $advice->shares()->pluck('advisor_id')->toArray();

        // Sync new advisors
        $advice->shares()->sync($newAdvisors);

        // Get new advisors after sync
        $newAdvisors = $advice->shares()->pluck('advisor_id')->toArray();

        // Find added advisors
        $addedAdvisors = array_diff($newAdvisors, $currentAdvisors);
        foreach ($addedAdvisors as $advisorId) {
            $advisor = User::find($advisorId);
            event(new AdviceSharedAdvisorAdded(
                $advice,
                $user,
                $advisor
            ));
        }

        // Find removed advisors
        $removedAdvisors = array_diff($currentAdvisors, $newAdvisors);
        foreach ($removedAdvisors as $advisorId) {
            $advisor = User::find($advisorId);
            event(new AdviceSharedAdvisorRemoved(
                $advice,
                $user,
                $advisor
            ));
        }
    }
}
