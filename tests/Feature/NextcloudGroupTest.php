<?php

use App\Contracts\NextcloudUserClientContract;
use App\Models\Group;
use App\Models\User;
use App\Nextcloud\Data\NextcloudUser;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function makeNcUser(string $id, string $email, string $displayname = 'Test User', bool $enabled = true): NextcloudUser
{
    return new NextcloudUser(
        id: $id,
        email: $email,
        displayname: $displayname,
        enabled: $enabled,
        groups: ['TestGruppe'],
    );
}

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->group = Group::factory()->create([
        'name' => 'Test Initiative',
        'nextcloud_group_name' => 'TestGruppe',
    ]);
    $this->group->users()->attach($this->admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->admin);
});

// ──────────────────────────────────────────────
// index: comparison view
// ──────────────────────────────────────────────

test('index returns nextcloudConfigured=false when group has no nextcloud_group_name', function () {
    $this->group->update(['nextcloud_group_name' => null]);

    $response = $this->get(route('groups.nextcloud', $this->group));

    $response->assertInertia(fn ($page) => $page
        ->component('Groups/Nextcloud')
        ->where('nextcloudConfigured', false)
        ->has('items', 0)
    );
});

test('index: nc user matched with crm group member shows crm_is_group_member=true', function () {
    $crmUser = User::factory()->create(['email' => 'user@example.com']);
    $this->group->users()->attach($crmUser, ['is_admin' => false]);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([makeNcUser('nc1', 'user@example.com', 'Test User')]);
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    // items.0 = nc user (NC email first), items.1 = admin (CRM-only)
    $response->assertInertia(fn ($page) => $page
        ->component('Groups/Nextcloud')
        ->where('nextcloudConfigured', true)
        ->has('items', 2)
        ->where('items.0.nc_id', 'nc1')
        ->where('items.0.crm_is_group_member', true)
        ->where('items.0.crm_user.email', 'user@example.com')
    );
});

test('index: nc user in crm but not in this group shows crm_is_group_member=false', function () {
    $otherGroup = Group::factory()->create();
    $crmUser = User::factory()->create(['email' => 'other@example.com']);
    $otherGroup->users()->attach($crmUser, ['is_admin' => false]);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([makeNcUser('nc2', 'other@example.com')]);
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    // items.0 = nc user (NC email first), items.1 = admin (CRM-only)
    $response->assertInertia(fn ($page) => $page
        ->has('items', 2)
        ->where('items.0.nc_id', 'nc2')
        ->where('items.0.crm_is_group_member', false)
        ->where('items.0.crm_user.email', 'other@example.com')
    );
});

test('index: nc user not in crm shows crm_user=null', function () {
    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([makeNcUser('nc3', 'nobody@example.com')]);
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    // items.0 = nc user (NC email first), items.1 = admin (CRM-only)
    $response->assertInertia(fn ($page) => $page
        ->has('items', 2)
        ->where('items.0.nc_id', 'nc3')
        ->where('items.0.crm_user', null)
        ->where('items.0.crm_is_group_member', null)
    );
});

test('index: crm group member not in nc appears with nc_id=null', function () {
    $crmUser = User::factory()->create(['email' => 'crm-only@example.com']);
    $this->group->users()->attach($crmUser, ['is_admin' => false]);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([]); // no NC users
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    // Both admin (from beforeEach) and crmUser are in the group with no NC counterpart
    $response->assertInertia(fn ($page) => $page
        ->has('items', 2)
        ->where('items.1.nc_id', null)
        ->where('items.1.crm_is_group_member', true)
        ->where('items.1.crm_user.email', 'crm-only@example.com')
    );
});

test('index: combines nc users and crm-only members in one list', function () {
    $crmUser = User::factory()->create(['email' => 'crm-only@example.com']);
    $this->group->users()->attach($crmUser, ['is_admin' => false]);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([makeNcUser('nc4', 'nc-only@example.com')]);
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    // Admin user (already attached in beforeEach) has no NC account
    // + the crm-only user has no NC account
    // + the nc-only user has no CRM account
    // total items = 2 (crm-only) + 1 (nc-only) = 3, but admin also counts
    // Actually admin is in group too, so: admin (crm-only) + crmUser (crm-only) + nc4 (nc-only) = 3
    $response->assertInertia(fn ($page) => $page
        ->has('items', 3)
    );
});

test('index: admin appears as crm-only when not in nc group', function () {
    // Only the admin is in the group, no NC users
    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getGroupMembersWithDetails')
            ->with('TestGruppe')
            ->andReturn([]);
    });

    $response = $this->get(route('groups.nextcloud', $this->group));

    $response->assertInertia(fn ($page) => $page
        ->has('items', 1)
        ->where('items.0.nc_id', null)
        ->where('items.0.crm_is_group_member', true)
    );
});

// ──────────────────────────────────────────────
// import
// ──────────────────────────────────────────────

test('import creates crm user and attaches to group', function () {
    Notification::fake();

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getUser')
            ->with('nc-new')
            ->andReturn(makeNcUser('nc-new', 'new@example.com', 'Max Mustermann'));
    });

    $this->post(route('groups.nextcloud.import', [$this->group, 'nc-new']), [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);

    $user = User::where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->first_name)->toBe('Max');
    expect($user->last_name)->toBe('Mustermann');
    expect($this->group->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

test('import fails when crm user with that email already exists', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getUser')
            ->with('nc-existing')
            ->andReturn(makeNcUser('nc-existing', 'existing@example.com'));
    });

    $response = $this->post(route('groups.nextcloud.import', [$this->group, 'nc-existing']), [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::where('email', 'existing@example.com')->count())->toBe(1);
});

// ──────────────────────────────────────────────
// addToGroup
// ──────────────────────────────────────────────

test('addToGroup attaches existing crm user to group', function () {
    $crmUser = User::factory()->create(['email' => 'existing@example.com']);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getUser')
            ->with('nc-ext')
            ->andReturn(makeNcUser('nc-ext', 'existing@example.com'));
    });

    $this->post(route('groups.nextcloud.add-to-group', [$this->group, 'nc-ext']));

    expect($this->group->users()->where('users.id', $crmUser->id)->exists())->toBeTrue();
});

test('addToGroup fails when no crm user with that email exists', function () {
    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getUser')
            ->with('nc-ghost')
            ->andReturn(makeNcUser('nc-ghost', 'ghost@example.com'));
    });

    $response = $this->post(route('groups.nextcloud.add-to-group', [$this->group, 'nc-ghost']));

    $response->assertSessionHasErrors('nc_user');
});

test('addToGroup fails when crm user is already in group', function () {
    $crmUser = User::factory()->create(['email' => 'member@example.com']);
    $this->group->users()->attach($crmUser, ['is_admin' => false]);

    $this->mock(NextcloudUserClientContract::class, function ($mock) {
        $mock->shouldReceive('getUser')
            ->with('nc-member')
            ->andReturn(makeNcUser('nc-member', 'member@example.com'));
    });

    $response = $this->post(route('groups.nextcloud.add-to-group', [$this->group, 'nc-member']));

    $response->assertSessionHasErrors('nc_user');
});
