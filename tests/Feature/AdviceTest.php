<?php

use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->advisor = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actAsGroup($this->group);
    Config::set('app.group_context', 'global');
});

test('can be created with sharing', function () {
    Advice::factory()->withSharing()->create();
    $this->assertTrue(true);
});

test('can be created with sendable', function () {
    Advice::factory()->withSendable()->create();
    $this->assertTrue(true);
});

test('advices table can be indexed by regular advisor', function () {
    createAdviceWithAndWithoutAdvisor($this->advisor);
    createAdviceWithAndWithoutAdvisor($this->admin);

    $this->withoutExceptionHandling();

    $this->actingAs($this->advisor)->get('advices')->assertOk();
});

test('advices table can be indexed by admin', function () {
    createAdviceWithAndWithoutAdvisor($this->admin);
    createAdviceWithAndWithoutAdvisor($this->advisor);

    $this->actingAs($this->admin)->get('advices')->assertOk();
});

test('advices map can be indexed by regular advisor', function () {
    createAdviceWithAndWithoutAdvisor($this->advisor);
    createAdviceWithAndWithoutAdvisor($this->admin);

    $this->actingAs($this->advisor)->get('advicesmap')->assertOk();
});

test('advices map can be indexed by admin', function () {
    createAdviceWithAndWithoutAdvisor($this->admin);
    createAdviceWithAndWithoutAdvisor($this->advisor);

    $this->actingAs($this->admin)->get('advicesmap')->assertOk();
});

function createAdviceWithAndWithoutAdvisor(User $advisor)
{

    FormDefinitionToAdvice::factory()->withAdvice()->create();

    $advice = Advice::firstOrFail();
    $advice->advisor_id = $advisor->id;
    $advice->save();

    FormDefinitionToAdvice::factory()->withAdvice()->create();

    $advice = Advice::factory()->count(2)->create();
    $adviceWithSendAble = Advice::factory()->withSendable()->count(2)->create();

    $advisor = User::factory()->create();

    $advice[0]->update(['advisor_id' => $advisor->id]);
    $adviceWithSendAble[0]->update(['advisor_id' => $advisor->id]);
}

test('advisor can be updated', function () {

    $this->actingAs($this->admin);

    $advisors = User::factory()->count(3)->create();

    $advice = Advice::factory()->create();

    $advisor = $advisors->random();

    $this->put(route('api.advices.update', $advice), [
        'advisor_id' => $advisors[0]->uuid,
    ])->assertJsonFragment([
        'advisor_id' => $advisors[0]->uuid,
    ]);
});
