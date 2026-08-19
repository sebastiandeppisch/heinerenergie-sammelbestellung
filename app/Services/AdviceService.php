<?php

declare(strict_types=1);

namespace App\Services;

use App\Context\GroupContextContract;
use App\Data\DataProtectedAdviceData;
use App\Data\FormSubmissionData;
use App\Events\Advice\AdviceSharedAdvisorAdded;
use App\Events\Advice\AdviceSharedAdvisorRemoved;
use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormSubmission;
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

    /**
     * @return Collection<int, DataProtectedAdviceData>
     */
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

        $query = Advice::query()
            ->with('status', 'group', 'group.parent', 'advisor', 'shares')
            ->where(function ($query) use ($user, $permissions): void {
                $query
                    // User is the advisor
                    ->where('advisor_id', $user->id)
                    // OR user is in shares
                    ->orWhereIn('id', $permissions['sharedAdviceIds'])
                    // OR advice has no advisor AND user is member/admin of group
                    ->orWhere(function ($subQuery) use ($permissions): void {
                        $subQuery->whereNull('advisor_id')
                            ->whereIn('group_id', $permissions['memberGroupIds']);
                    })
                    // OR user is admin of the group (can see all)
                    ->orWhereIn('group_id', $permissions['adminGroupIds']);
            });

        if (count($permissions['memberGroupIds']) > 0) {
            $query->whereIn('group_id', $permissions['memberGroupIds']);
        }

        if (count($permissions['adminGroupIds']) > 0) {
            $query->whereIn('group_id', $permissions['adminGroupIds']);
        }

        return $query
            ->get()
            ->map(fn (Advice $advice): DataProtectedAdviceData => DataProtectedAdviceData::fromModel($advice, $user, $isGroupAdmin));
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
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
     * Fetch the form submission for an advice, with the fields removed that are
     * mapped to personal data in the form's FormDefinitionToAdvice mapping.
     */
    public function getFilteredFormSubmission(Advice $advice): ?FormSubmissionData
    {
        $formSubmission = FormSubmission::where('advice_id', $advice->id)->with('submissionFields', 'submissionFields.options')->first();

        if ($formSubmission === null) {
            return null;
        }

        $adviceCreator = FormDefinitionToAdvice::where('form_definition_id', $formSubmission->form_definition_id)->first();

        $personalFieldIds = collect([
            $adviceCreator?->address_field_id,
            $adviceCreator?->email_field_id,
            $adviceCreator?->phone_field_id,
            $adviceCreator?->first_name_field_id,
            $adviceCreator?->last_name_field_id,
            $adviceCreator?->advice_type_field_id,
        ])->filter();

        $formSubmission->setRelation(
            'submissionFields',
            $formSubmission->submissionFields
                ->reject(fn ($submissionField) => $personalFieldIds->contains($submissionField->form_field_id))
                ->values()
        );

        return FormSubmissionData::fromModel($formSubmission);
    }

    /**
     * @param  Collection<int, User>|Collection<int, int>  $newAdvisors
     */
    public function syncShares(Advice $advice, Collection $newAdvisors, ?User $user): void
    {

        $newAdvisors = $newAdvisors->map(function (User|int $user): User {
            if ($user instanceof User) {
                return $user;
            }

            return User::findOrFail($user);
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
