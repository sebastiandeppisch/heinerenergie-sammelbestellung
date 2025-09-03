<?php

namespace App\Services;

use App\Context\GroupContextContract;
use App\Data\DataProtectedAdviceData;
use App\Events\Advice\AdviceSharedAdvisorAdded;
use App\Events\Advice\AdviceSharedAdvisorRemoved;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\ValueObjects\Meter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdviceService
{
    public function __construct(
        private readonly GroupContextContract $groupContext
    ) {}

    public function getAdvicesListForUser(User $user): Collection
    {
        $permissions = $this->getUserAdvicePermissions($user);

        if ($this->groupContext->getCurrentGroup()) {
            $isGroupAdmin = $this->groupContext->isActingAsDirectAdmin($user, $this->groupContext->getCurrentGroup());
        } elseif ($this->groupContext->isActingAsSystemAdmin($user)) {
            $isGroupAdmin = true;
        } else {
            $isGroupAdmin = false;
        }

        return Advice::query()
            ->with('status', 'group', 'group.parent', 'advisor', 'shares')
            ->where(function ($query) use ($user, $permissions) {
                $query
                    // User is the advisor
                    ->where('advisor_id', $user->id)
                    // OR user is in shares
                    ->orWhereIn('id', $permissions['sharedAdviceIds'])
                    // OR advice has no advisor AND user is member/admin of group
                    ->orWhere(function ($subQuery) use ($permissions) {
                        $subQuery->whereNull('advisor_id')
                            ->whereIn('group_id', $permissions['memberGroupIds']);
                    })
                    // OR user is admin of the group (can see all)
                    ->orWhereIn('group_id', $permissions['adminGroupIds']);
            })
            ->get()
            ->map(fn ($advice) => DataProtectedAdviceData::fromModel($advice, $user, $isGroupAdmin));
    }

    private function getUserAdvicePermissions(User $user): array
    {
        $allGroups = Group::with('users')->get();

        $adminGroupIds = collect();
        foreach ($allGroups as $group) {
            if ($this->groupContext->isActingAsTransitiveAdmin($user, $group)) {
                $adminGroupIds->push($group->id);
            }
        }

        $memberGroupIds = collect();
        foreach ($allGroups as $group) {
            if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $group)) {
                $memberGroupIds->push($group->id);
            }
        }

        $sharedAdviceIds = DB::table('sharings')
            ->where('advisor_id', $user->id)
            ->where('sharing_type', Advice::class)
            ->pluck('sharing_id');

        return [
            'adminGroupIds' => $adminGroupIds,
            'memberGroupIds' => $memberGroupIds,
            'sharedAdviceIds' => $sharedAdviceIds,
        ];
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
        return $user->can('update', $advice);
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
