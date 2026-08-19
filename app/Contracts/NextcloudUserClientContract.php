<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Nextcloud\Data\NextcloudUser;

interface NextcloudUserClientContract
{
    /** @return string[] */
    public function getGroupMembers(string $groupId): array;

    public function getUser(string $userId): NextcloudUser;

    /** @return NextcloudUser[] */
    public function getGroupMembersWithDetails(string $groupId): array;
}
