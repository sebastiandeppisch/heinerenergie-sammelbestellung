<?php

namespace Tests\Support;

use App\Contracts\NextcloudUserClientContract;
use App\Nextcloud\Data\NextcloudUser;
use RuntimeException;

class MockNextcloudUserClient implements NextcloudUserClientContract
{
    /** @var array<string, NextcloudUser> */
    private array $users;

    /** @var array<string, string[]> */
    private array $groups;

    public function __construct()
    {
        $this->users = [
            'berater.mueller' => new NextcloudUser(
                id: 'berater.mueller',
                email: 'mueller@example.com',
                displayname: 'Hans Müller',
                enabled: true,
                groups: ['Berater'],
            ),
            'berater.schmidt' => new NextcloudUser(
                id: 'berater.schmidt',
                email: 'schmidt@example.com',
                displayname: 'Anna Schmidt',
                enabled: true,
                groups: ['Berater'],
            ),
            'berater.inactive' => new NextcloudUser(
                id: 'berater.inactive',
                email: 'inactive@example.com',
                displayname: 'Inaktiver User',
                enabled: false,
                groups: ['Berater'],
            ),
        ];

        $this->groups = [
            'Berater' => ['berater.mueller', 'berater.schmidt', 'berater.inactive'],
        ];
    }

    /** @return string[] */
    public function getGroupMembers(string $groupId): array
    {
        return $this->groups[$groupId] ?? throw new RuntimeException("Nextcloud group '{$groupId}' not found");
    }

    public function getUser(string $userId): NextcloudUser
    {
        return $this->users[$userId] ?? throw new RuntimeException("Nextcloud user '{$userId}' not found");
    }

    /** @return NextcloudUser[] */
    public function getGroupMembersWithDetails(string $groupId): array
    {
        return array_map(
            fn (string $id) => $this->getUser($id),
            $this->getGroupMembers($groupId),
        );
    }
}
