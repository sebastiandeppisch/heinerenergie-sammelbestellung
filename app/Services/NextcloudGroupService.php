<?php

namespace App\Services;

use App\Contracts\NextcloudUserClientContract;
use App\Data\CrmUserData;
use App\Data\NextcloudGroupUserData;
use App\Models\Group;
use App\Models\User;
use App\Nextcloud\Data\NextcloudUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class NextcloudGroupService
{
    public function __construct(
        private readonly NextcloudUserClientContract $client,
    ) {}

    /**
     * Returns a merged comparison of Nextcloud group members and CRM group members.
     *
     * @return Collection<int, NextcloudGroupUserData>
     * @throws \RuntimeException if the Nextcloud group cannot be fetched
     */
    public function getComparisonItems(Group $group): Collection
    {
        $ncUsers = $this->client->getGroupMembersWithDetails($group->nextcloud_group_name);
        $ncUsersByEmail = collect($ncUsers)->keyBy('email')->filter();

        $crmGroupMembers = $group->users()->get()->keyBy('email');

        $ncEmails = $ncUsersByEmail->keys()->filter()->values()->all();
        $crmMatchedNotInGroup = User::whereIn('email', $ncEmails)
            ->whereNotIn('id', $crmGroupMembers->pluck('id'))
            ->get()
            ->keyBy('email');

        $allEmails = $ncUsersByEmail->keys()
            ->merge($crmGroupMembers->keys())
            ->unique()
            ->filter()
            ->values();

        return $allEmails->map(function (string $email) use ($ncUsersByEmail, $crmGroupMembers, $crmMatchedNotInGroup) {
            $ncUser = $ncUsersByEmail->get($email);
            $crmUser = $crmGroupMembers->get($email) ?? $crmMatchedNotInGroup->get($email);
            $isGroupMember = $crmUser ? $crmGroupMembers->has($email) : null;

            return new NextcloudGroupUserData(
                nc_id: $ncUser?->id,
                nc_email: $ncUser?->email,
                nc_displayname: $ncUser?->displayname,
                nc_enabled: $ncUser !== null ? $ncUser->enabled : null,
                crm_user: $crmUser ? CrmUserData::fromUser($crmUser) : null,
                crm_is_group_member: $isGroupMember,
            );
        });
    }

    /**
     * @throws \RuntimeException if the Nextcloud user cannot be fetched
     */
    public function getNcUser(string $ncUserId): NextcloudUser
    {
        return $this->client->getUser($ncUserId);
    }

    public function crmUserExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function findCrmUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function isGroupMember(Group $group, User $user): bool
    {
        return $group->users()->where('users.id', $user->id)->exists();
    }

    public function createAndAttachUser(Group $group, NextcloudUser $ncUser, string $firstName, string $lastName, bool $sendEmail): User
    {
        return DB::transaction(function() use ($firstName, $lastName, $sendEmail, $ncUser, $group): User{
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $ncUser->email,
                'password' => Str::random(32),
            ]);

            $group->users()->attach($user->id, ['is_admin' => false]);

            if ($sendEmail) {
                Password::sendResetLink(['email' => $user->email]);
            }


            return $user;
        });
    }

    public function attachUserToGroup(Group $group, User $user): void
    {
        $group->users()->attach($user->id, ['is_admin' => false]);
    }
}
