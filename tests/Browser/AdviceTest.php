<?php

use App\Contracts\NextcloudFileClientContract;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MockNextcloudFileClient;

uses(RefreshDatabase::class);

beforeEach(function () {

    app()->bind(NextcloudFileClientContract::class, MockNextcloudFileClient::class);

    $this->user = User::factory()->create();
    $this->group = Group::factory()->create([
        'name' => 'Test Initiative',
        'nextcloud_search_path' => '/Beratungen',
    ]);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('advice no smoke & no js errors', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});
