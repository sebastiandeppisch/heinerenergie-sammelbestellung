<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Services\FormEmbedAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('form can be loaded directly without a domain whitelist', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => null]);

    $response = $this->get(route('form.show', $formDefinition));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Forms/Show')
        ->where('embedBlocked', false)
        ->has('formToken')
        ->has('formDefinition')
    );
});

test('the allowed embed domains are not exposed on the public form pages', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['secret-partner.example']]);
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
    ]);

    $showResponse = $this->get(route('form.show', $formDefinition));
    $showResponse->assertInertia(fn (Assert $page) => $page
        ->where('formDefinition.allowed_embed_domains', null)
    );

    $submitResponse = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => 'Sample text',
        '_form_token' => app(FormEmbedAccessService::class)->issueToken($formDefinition),
    ]);
    $submitResponse->assertInertia(fn (Assert $page) => $page
        ->where('formDefinition.allowed_embed_domains', null)
    );

    expect($showResponse->getContent())->not->toContain('secret-partner.example');
    expect($submitResponse->getContent())->not->toContain('secret-partner.example');
});

test('form is blocked when loaded in an iframe from a non-whitelisted domain', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['allowed.example']]);

    $response = $this->get(route('form.show', $formDefinition), [
        'Sec-Fetch-Dest' => 'iframe',
        'Referer' => 'https://not-allowed.example/page',
    ]);

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Forms/Show')
        ->where('embedBlocked', true)
        ->where('formDefinition', null)
    );
});

test('form loads when loaded in an iframe from a whitelisted domain', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['allowed.example']]);

    $response = $this->get(route('form.show', $formDefinition), [
        'Sec-Fetch-Dest' => 'iframe',
        'Referer' => 'https://allowed.example/page',
    ]);

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Forms/Show')
        ->where('embedBlocked', false)
        ->has('formToken')
        ->has('formDefinition')
    );
});

test('submitting without a form token is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => 'Sample text',
        '_form_token' => '',
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('form_submissions', 0);
});

test('submitting with an invalid form token is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => 'Sample text',
        '_form_token' => 'not-a-valid-token',
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('form_submissions', 0);
});

test('submitting with a valid form token from another form is rejected', function () {
    $formDefinitionA = FormDefinition::factory()->create();
    $formDefinitionB = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinitionB->id,
        'type' => FieldType::TEXT,
    ]);

    $tokenForOtherForm = app(FormEmbedAccessService::class)->issueToken($formDefinitionA);

    $response = $this->post(route('form.submit', $formDefinitionB), [
        $formField->uuid => 'Sample text',
        '_form_token' => $tokenForOtherForm,
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('form_submissions', 0);
});

test('submitting with a valid form token succeeds without a session cookie', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
    ]);

    // Simulates a cross-site iframe request where no session/XSRF cookie reaches
    // the server at all — the endpoint must not depend on it.
    $response = $this->withUnencryptedCookies([])->post(route('form.submit', $formDefinition), [
        $formField->uuid => 'Sample text',
        '_form_token' => app(FormEmbedAccessService::class)->issueToken($formDefinition),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id,
    ]);
});
