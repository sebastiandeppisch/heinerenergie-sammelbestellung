<?php

use App\Enums\FieldType;
use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->advisor = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actAsGroup($this->group);
    Config::set('app.group_context', 'global');
});

test('can be created with sharing', function (): void {
    Advice::factory()->withSharing()->create();
    $this->assertTrue(true);
});

test('can be created with sendable', function (): void {
    Advice::factory()->withSendable()->create();
    $this->assertTrue(true);
});

test('advices table can be indexed by regular advisor', function (): void {
    createAdviceWithAndWithoutAdvisor($this->advisor);
    createAdviceWithAndWithoutAdvisor($this->admin);

    $this->withoutExceptionHandling();

    $this->actingAs($this->advisor)->get('advices')->assertOk();
});

test('advices table can be indexed by admin', function (): void {
    createAdviceWithAndWithoutAdvisor($this->admin);
    createAdviceWithAndWithoutAdvisor($this->advisor);

    $this->actingAs($this->admin)->get('advices')->assertOk();
});

test('group column is hidden for regular advisors', function (): void {
    Group::factory()->create(['parent_id' => $this->group->id]);

    app(SessionService::class)->actAsGroup($this->group);

    $this->actingAs($this->advisor)->get('advices')
        ->assertInertia(fn ($page) => $page->component('Advices')->where('showGroupColumn', false));
});

test('group column is shown for group admins acting in a non-leaf group', function (): void {
    Group::factory()->create(['parent_id' => $this->group->id]);

    app(SessionService::class)->actAsGroup($this->group, true);

    $this->actingAs($this->advisor)->get('advices')
        ->assertInertia(fn ($page) => $page->component('Advices')->where('showGroupColumn', true));
});

test('group column is hidden for group admins acting in a leaf group', function (): void {
    app(SessionService::class)->actAsGroup($this->group, true);

    $this->actingAs($this->advisor)->get('advices')
        ->assertInertia(fn ($page) => $page->component('Advices')->where('showGroupColumn', false));
});

test('group column is shown for system admins', function (): void {
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs($this->admin)->get('advices')
        ->assertInertia(fn ($page) => $page->component('Advices')->where('showGroupColumn', true));
});

test('form submission preview shows only non-personal fields to group members', function (): void {
    $adviceCreator = FormDefinitionToAdvice::factory()->withAdvice()->create();

    $advice = Advice::firstOrFail();
    $formSubmission = FormSubmission::firstOrFail();

    $extraField = FormField::factory()->create([
        'type' => FieldType::TEXT,
        'label' => 'Wobei wird Hilfe benötigt?',
        'required' => false,
        'min_length' => null,
    ]);
    $adviceCreator->formDefinition->fields()->save($extraField);
    $extraField->createSubmissionField($formSubmission, 'Balkonkraftwerk');

    $advice->group->users()->attach($this->advisor->id, ['is_admin' => false]);

    $response = $this->actingAs($this->advisor)->getJson(route('api.advices.formSubmission', $advice))->assertOk();

    $labels = collect($response->json('formSubmission.fields'))->pluck('field.label');
    expect($labels->all())->toBe(['Wobei wird Hilfe benötigt?']);
});

test('form submission preview returns null when the advice has no submission', function (): void {
    $this->group->users()->attach($this->advisor->id, ['is_admin' => false]);

    $advice = Advice::factory()->create(['group_id' => $this->group->id]);

    $this->actingAs($this->advisor)->getJson(route('api.advices.formSubmission', $advice))
        ->assertOk()
        ->assertExactJson(['formSubmission' => null]);
});

test('form submission preview is forbidden for users outside the advice group', function (): void {
    $advice = Advice::factory()->create(['group_id' => $this->group->id]);
    FormSubmission::factory()->create([
        'advice_id' => $advice->id,
        'group_id' => $this->group->id,
    ]);

    $this->actingAs($this->advisor)->getJson(route('api.advices.formSubmission', $advice))->assertForbidden();
});

test('advices map can be indexed by regular advisor', function (): void {
    createAdviceWithAndWithoutAdvisor($this->advisor);
    createAdviceWithAndWithoutAdvisor($this->admin);

    $this->actingAs($this->advisor)->get('advicesmap')->assertOk();
});

test('advices map can be indexed by admin', function (): void {
    createAdviceWithAndWithoutAdvisor($this->admin);
    createAdviceWithAndWithoutAdvisor($this->advisor);

    $this->actingAs($this->admin)->get('advicesmap')->assertOk();
});

function createAdviceWithAndWithoutAdvisor(User $advisor): void
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

test('advisor can be updated', function (): void {

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

test('setAdvisors API sets shared advisors on advice', function (): void {
    $advice = Advice::factory()->create(['group_id' => $this->group->id]);
    $advisorsToShare = User::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/advices/{$advice->uuid}/advisors", [
            'advisors' => $advisorsToShare->map(fn (User $u) => $u->uuid)->values()->all(),
        ]);

    $response->assertOk();
    $advice->refresh();
    expect($advice->shares->pluck('id')->sort()->values()->all())
        ->toEqual($advisorsToShare->pluck('id')->sort()->values()->all());
});

$validAdviceData = [
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'phone' => '01234567890',
    'email' => 'max@mustermann.de',
    'street' => 'Musterstraße',
    'street_number' => '1',
    'zip' => '12345',
    'city' => 'Musterstadt',
    'type' => 0,
];

test('system admin must select a group when creating an advice', function () use (&$validAdviceData): void {
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs($this->admin)
        ->post(route('advices.store'), $validAdviceData)
        ->assertSessionHasErrors('group_id');

    expect(Advice::count())->toBe(0);
});

test('system admin can create an advice when group is provided', function () use (&$validAdviceData): void {
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs($this->admin)
        ->post(route('advices.store'), array_merge($validAdviceData, ['group_id' => $this->group->uuid]))
        ->assertRedirect();

    expect(Advice::where('first_name', 'Max')->exists())->toBeTrue();
});
