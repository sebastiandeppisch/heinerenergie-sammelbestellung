<?php

use App\Enums\AdviceStatusResult;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('advices table renders without errors', function () {
    visit(route('advices'))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});

test('advices table shows create button', function () {
    visit(route('advices'))
        ->assertNoSmoke()
        ->assertVisible('@new-advice-button')
        ->assertNoJavaScriptErrors();
});

test('advices table search filters rows', function () {
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
    ]);

    visit(route('advices'))
        ->assertNoSmoke()
        ->assertSee('Max')
        ->assertSee('Erika')
        ->fill('input[placeholder="Suchen..."]', 'Max')
        ->assertSee('Max')
        ->assertDontSee('Erika')
        ->assertNoJavaScriptErrors();
});

test('advices table inline edit buttons are visible', function () {
    $status = AdviceStatus::create([
        'name' => 'Test Status',
        'result' => AdviceStatusResult::New,
        'group_id' => $this->group->id,
    ]);

    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'advice_status_id' => $status->id,
    ]);

    visit(route('advices'))
        ->assertNoSmoke()
        ->assertSee('Test')
        ->assertVisible('[data-test="edit-status"]')
        ->assertVisible('[data-test="edit-advisor"]')
        ->assertNoJavaScriptErrors();
});
