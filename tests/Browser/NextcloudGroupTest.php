<?php

use App\Contracts\NextcloudUserClientContract;
use App\Models\Group;
use App\Models\User;
use App\Nextcloud\NextcloudUserClient;
use App\Services\SessionService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const NC_USER_ID = 'nc-test-user';
const NC_USER_EMAIL = 'nc-user@example.com';
const NC_DISPLAY_NAME = 'Max Mustermann';
const NC_GROUP_NAME = 'TestGruppe';

beforeEach(function (): void {
    app()->singleton(
        NextcloudUserClientContract::class,
        NextcloudUserClient::class
    );
    Http::fake([
        '*/ocs/v1.php/cloud/groups/*' => Http::response([
            'ocs' => [
                'meta' => ['statuscode' => 100, 'status' => 'ok'],
                'data' => ['users' => [NC_USER_ID]],
            ],
        ]),
        '*/ocs/v1.php/cloud/users/'.NC_USER_ID => Http::response([
            'ocs' => [
                'meta' => ['statuscode' => 100, 'status' => 'ok'],
                'data' => [
                    'id' => NC_USER_ID,
                    'email' => NC_USER_EMAIL,
                    'displayname' => NC_DISPLAY_NAME,
                    'enabled' => '1',
                    'groups' => [NC_GROUP_NAME],
                ],
            ],
        ]),
    ]);

    $this->admin = User::factory()->create();
    $this->group = Group::factory()->create([
        'name' => 'Test Initiative',
        'nextcloud_group_name' => NC_GROUP_NAME,
    ]);
    $this->group->users()->attach($this->admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->admin);
});

test('nextcloud page loads with user list when group name is configured', function (): void {
    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->assertSee('Nextcloud-Abgleich')
        ->assertSee(NC_DISPLAY_NAME)
        ->assertSee(NC_USER_EMAIL);
});

test('nextcloud page shows config hint when no group name is set', function (): void {
    $this->group->update(['nextcloud_group_name' => null]);

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->assertSee('Kein Nextcloud-Gruppenname konfiguriert')
        ->assertDontSee(NC_DISPLAY_NAME);
});

test('unmatched nc user shows import button', function (): void {
    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->assertSee('Nicht im CRM')
        ->assertSee('Importieren');
});

test('matched nc user shows crm badge instead of import button', function (): void {
    User::factory()->create(['email' => NC_USER_EMAIL]);

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->assertSee('Im CRM')
        ->assertDontSee('Importieren');
});

test('import dialog opens with prefilled name from nc displayname', function (): void {
    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->click('Importieren')
        ->assertSee('Benutzer ins CRM importieren')
        ->assertValue('import-first-name', 'Max')
        ->assertValue('import-last-name', 'Mustermann')
        ->assertSee(NC_USER_EMAIL);
});

test('crm user in different group shows add-to-group button', function (): void {
    User::factory()->create(['email' => NC_USER_EMAIL]);

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->assertSee('Im CRM, nicht in Gruppe')
        ->assertSee('Zur Gruppe hinzufügen');
});

test('add-to-group adds existing crm user to the group', function (): void {
    $existingUser = User::factory()->create(['email' => NC_USER_EMAIL]);

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->click('@add-to-group')
        ->assertSee('Im CRM')
        ->assertDontSee('Zur Gruppe hinzufügen');

    expect($this->group->users()->where('users.id', $existingUser->id)->exists())->toBeTrue();
});

test('import with send-email checkbox sends password reset notification', function (): void {
    Notification::fake();

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->click('Importieren')
        ->check('import-send-email')
        ->click('@import-confirm');

    $newUser = User::where('email', NC_USER_EMAIL)->firstOrFail();
    Notification::assertSentTo($newUser, ResetPassword::class);
});

test('import without send-email checkbox sends no notification', function (): void {
    Notification::fake();

    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->click('Importieren')
        ->click('@import-confirm');

    $newUser = User::where('email', NC_USER_EMAIL)->firstOrFail();
    Notification::assertNotSentTo($newUser, ResetPassword::class);
});

test('import creates crm user and shows in crm badge', function (): void {
    visit(route('groups.nextcloud', $this->group))
        ->assertNoSmoke()
        ->click('Importieren')
        ->assertSee('Benutzer ins CRM importieren')
        ->click('@import-confirm')
        ->assertSee('Im CRM')
        ->assertDontSee('Importieren');

    expect(User::where('email', NC_USER_EMAIL)->exists())->toBeTrue();
    $newUser = User::where('email', NC_USER_EMAIL)->first();
    expect($newUser->groups()->where('groups.id', $this->group->id)->exists())->toBeTrue();
});
