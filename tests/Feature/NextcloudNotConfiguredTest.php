<?php

use App\Contracts\NextcloudFileClientContract;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Nextcloud\WebDavNextcloudFileClient;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('resolving the webdav client without a configured username does not crash', function (): void {
    Config::set('nextcloud.base_url', 'https://cloud.example.com');
    Config::set('nextcloud.username', null);
    Config::set('nextcloud.password', null);

    app(WebDavNextcloudFileClient::class);
})->throwsNoExceptions();

test('an advice route still works when nextcloud is not configured', function (): void {
    Config::set('nextcloud.base_url', 'https://cloud.example.com');
    Config::set('nextcloud.username', null);
    Config::set('nextcloud.password', null);
    Config::set('app.group_context', 'global');

    app()->singleton(NextcloudFileClientContract::class, WebDavNextcloudFileClient::class);

    $user = User::factory()->create();
    $group = Group::factory()->create(['nextcloud_search_path' => '/Beratungen']);
    $group->users()->attach($user->id, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    $advice = Advice::factory()->create([
        'advisor_id' => $user->id,
        'group_id' => $group->id,
    ]);

    actingAs($user)
        ->getJson("/api/advices/{$advice->uuid}/nextcloud/files")
        ->assertOk()
        ->assertJson([]);
});

test('advice show page hides the nextcloud files section when nextcloud is not configured', function (): void {
    Config::set('nextcloud.base_url', null);
    Config::set('nextcloud.username', null);
    Config::set('nextcloud.password', null);
    Config::set('app.group_context', 'global');

    $user = User::factory()->create();
    $group = Group::factory()->create(['nextcloud_search_path' => '/Beratungen']);
    $group->users()->attach($user->id, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    $advice = Advice::factory()->create([
        'advisor_id' => $user->id,
        'group_id' => $group->id,
    ]);

    actingAs($user)
        ->get(route('advices.show', $advice))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Advice')
            ->where('nextcloudConfigured', false)
        );
});

test('advice show page shows the nextcloud files section when nextcloud is configured', function (): void {
    Config::set('nextcloud.base_url', 'https://cloud.example.com');
    Config::set('nextcloud.username', 'nc-user');
    Config::set('nextcloud.password', 'secret');
    Config::set('app.group_context', 'global');

    $user = User::factory()->create();
    $group = Group::factory()->create(['nextcloud_search_path' => '/Beratungen']);
    $group->users()->attach($user->id, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    $advice = Advice::factory()->create([
        'advisor_id' => $user->id,
        'group_id' => $group->id,
    ]);

    actingAs($user)
        ->get(route('advices.show', $advice))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Advice')
            ->where('nextcloudConfigured', true)
        );
});
