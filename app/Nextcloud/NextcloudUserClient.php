<?php

namespace App\Nextcloud;

use App\Contracts\NextcloudUserClientContract;
use App\Nextcloud\Data\NextcloudUser;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class NextcloudUserClient implements NextcloudUserClientContract
{
    private readonly PendingRequest $http;

    private readonly string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('nextcloud.base_url'), '/');
        $this->http = $this->buildRequest();
    }

    private function buildRequest(?Pool $pool = null): PendingRequest
    {
        return ($pool ?? Http::getFacadeRoot())
            ->withBasicAuth(
                (string) config('nextcloud.username'),
                (string) config('nextcloud.password'),
            )
            ->withHeaders(['OCS-APIRequest' => 'true'])
            ->accept('application/json');
    }

    /** @return string[] */
    public function getGroupMembers(string $groupId): array
    {
        $response = $this->http->get("{$this->baseUrl}/ocs/v1.php/cloud/groups/".rawurlencode($groupId));

        if ($response->status() === 401) {
            throw new RuntimeException('Nextcloud authentication failed');
        }

        $data = $response->json();
        $statuscode = $data['ocs']['meta']['statuscode'] ?? 0;

        if ($statuscode !== 100) {
            throw new RuntimeException("Nextcloud group '{$groupId}' not found (statuscode: {$statuscode})");
        }

        return $data['ocs']['data']['users'] ?? [];
    }

    public function getUser(string $userId): NextcloudUser
    {
        $response = $this->http->get("{$this->baseUrl}/ocs/v1.php/cloud/users/".rawurlencode($userId));

        if ($response->status() === 401) {
            throw new RuntimeException('Nextcloud authentication failed');
        }

        $data = $response->json();
        $statuscode = $data['ocs']['meta']['statuscode'] ?? 0;

        if ($statuscode !== 100) {
            throw new RuntimeException("Nextcloud user '{$userId}' not found (statuscode: {$statuscode})");
        }

        $user = $data['ocs']['data'];

        return new NextcloudUser(
            id: $user['id'],
            email: $user['email'] ?? '',
            displayname: $user['displayname'] ?? '',
            enabled: ($user['enabled'] ?? '0') === '1',
            groups: $user['groups'] ?? [],
        );
    }

    /** @return NextcloudUser[] */
    public function getGroupMembersWithDetails(string $groupId): array
    {
        $userIds = $this->getGroupMembers($groupId);

        if (empty($userIds)) {
            return [];
        }

        $baseUrl = $this->baseUrl;

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $userId) => $this->buildRequest($pool)
                ->get("{$baseUrl}/ocs/v1.php/cloud/users/".rawurlencode($userId)),
            $userIds,
        ));

        $users = [];
        foreach ($responses as $response) {
            if ($response instanceof Throwable) {
                continue;
            }

            $data = $response->json();

            if (($data['ocs']['meta']['statuscode'] ?? 0) !== 100) {
                continue;
            }

            $user = $data['ocs']['data'];
            $users[] = new NextcloudUser(
                id: $user['id'],
                email: $user['email'] ?? '',
                displayname: $user['displayname'] ?? '',
                enabled: ($user['enabled'] ?? '0') === '1',
                groups: $user['groups'] ?? [],
            );
        }

        return $users;
    }
}
