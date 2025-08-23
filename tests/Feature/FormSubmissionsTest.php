<?php

use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('index page can be rendered', function () {
    $response = $this->get(route('form-submissions.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormSubmissions/Index')
    );
});

test('form submissions can be sorted by submitted_at ascending', function () {

    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now()->subDays(1),
        'form_name' => 'Test Form',
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
    ]);

    $response = $this->get(route('form-submissions.index', ['sortOrder' => 'asc']));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.id', $submissionA->uuid)
        ->where('formSubmissions.1.id', $submissionB->uuid)
    );
});

test('form submissions are sorted by submitted_at descending by default', function () {
    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now()->subDays(1),
        'form_name' => 'Test Form',
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
    ]);

    $response = $this->get(route('form-submissions.index'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.id', $submissionB->uuid)
        ->where('formSubmissions.1.id', $submissionA->uuid)
    );
});

test('form submissions can be sorted by form definition', function () {
    $submissionA = FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Test Form',
        'form_definition_id' => 'Test Form',
    ]);

    $submissionB = FormSubmission::factory()->create([
        'submitted_at' => now()->tomorrow(),
        'form_name' => 'Another Form',
        'form_definition_id' => 'Another Form',
    ]);

    $response = $this->get(route('form-submissions.index', ['groupByForm' => 'true']));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormSubmissions/Index')
        ->has('formSubmissions', 2)
        ->where('formSubmissions.0.form_name', 'Another Form')
        ->where('formSubmissions.1.form_name', 'Test Form')
    );
});
