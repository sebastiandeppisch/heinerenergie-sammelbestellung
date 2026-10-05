<?php

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('index page can be rendered', function (): void {
    $response = $this->get(route('form-submissions.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
    );
});

test('form submissions can be sorted by submitted_at ascending', function (): void {
    $formDefinition = FormDefinition::factory()->create(['group_id' => $this->group->id]);

    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now()->subDays(1),
        'form_name' => 'Test Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefinition->id,
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefinition->id,
    ]);

    $response = $this->get(route('form-submissions.index', ['sortOrder' => 'asc']));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.id', $submissionA->uuid)
        ->where('formSubmissions.1.id', $submissionB->uuid)
    );
});

test('form submissions are sorted by submitted_at descending by default', function (): void {
    $formDefinition = FormDefinition::factory()->create(['group_id' => $this->group->id]);

    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now()->subDays(1),
        'form_name' => 'Test Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefinition->id,
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefinition->id,
    ]);

    $response = $this->get(route('form-submissions.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.id', $submissionB->uuid)
        ->where('formSubmissions.1.id', $submissionA->uuid)
    );
});

test('form submissions can be sorted by form definition', function (): void {
    // Create formDefForAnother first so it gets a smaller ID and sorts first
    $formDefForAnother = FormDefinition::factory()->create(['group_id' => $this->group->id]);
    $formDefForTest = FormDefinition::factory()->create(['group_id' => $this->group->id]);

    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefForTest->id,
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now()->tomorrow(),
        'form_name' => 'Another Form',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefForAnother->id,
    ]);

    $response = $this->get(route('form-submissions.index', ['groupByForm' => 'true']));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.form_name', 'Another Form')
        ->where('formSubmissions.1.form_name', 'Test Form')
    );
});

test('view defaults to cards', function (): void {
    $response = $this->get(route('form-submissions.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
        ->where('view', 'cards')
    );
});

test('view can be set to table via query parameter', function (): void {
    $response = $this->get(route('form-submissions.index', ['view' => 'table']));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('FormSubmissions/Index')
        ->where('view', 'table')
    );
});

test('invalid view value is rejected', function (): void {
    $response = $this->get(route('form-submissions.index', ['view' => 'invalid']));
    $response->assertSessionHasErrors('view');
});

test('unconfirmed submissions are marked and can be filtered', /** @param list<string> $expected */ function (string $filter, array $expected): void {
    $formDefinition = FormDefinition::factory()->create(['group_id' => $this->group->id]);
    $attributes = ['group_id' => $this->group->id, 'form_definition_id' => $formDefinition->id];

    $plain = FormSubmission::factory()->create([...$attributes, 'submitted_at' => now()->subDays(3)]);
    $unconfirmed = FormSubmission::factory()->create([...$attributes, 'submitted_at' => now()->subDays(2), 'confirmation_token_hash' => str_repeat('a', 64)]);
    $confirmed = FormSubmission::factory()->create([...$attributes, 'submitted_at' => now()->subDay(), 'confirmation_token_hash' => str_repeat('b', 64), 'confirmed_at' => now()]);

    $submissions = ['plain' => $plain, 'unconfirmed' => $unconfirmed, 'confirmed' => $confirmed];

    $this->get(route('form-submissions.index', ['confirmation' => $filter, 'sortOrder' => 'asc']))
        ->assertInertia(function (Assert $page) use ($expected, $submissions, $filter): Assert {
            $page->component('FormSubmissions/Index')
                ->where('confirmation', $filter)
                ->has('formSubmissions', count($expected));

            foreach ($expected as $index => $name) {
                $page->where("formSubmissions.{$index}.id", $submissions[$name]->uuid)
                    ->where("formSubmissions.{$index}.awaiting_confirmation", $name === 'unconfirmed');
            }

            return $page;
        });
})->with([
    'all' => ['all', ['plain', 'unconfirmed', 'confirmed']],
    'only unconfirmed' => ['unconfirmed', ['unconfirmed']],
    'hide unconfirmed' => ['hide_unconfirmed', ['plain', 'confirmed']],
]);
