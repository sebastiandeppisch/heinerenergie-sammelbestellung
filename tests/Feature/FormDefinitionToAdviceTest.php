<?php

use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can be created by factory', function () {
    FormDefinitionToAdvice::factory()->create();
    $this->assertTrue(true);
});

test('submitting the form produces an advice', function () {

    $this->withoutExceptionHandling();

    $config = FormDefinitionToAdvice::factory()->create();

    $form = $config->formDefinition;

    $response = $this->post(route('form.submit', $form), [
        $config->firstNameField->uuid => fake()->firstName(),
        $config->lastNameField->uuid => fake()->lastName(),
        $config->emailField->uuid => fake()->safeEmail(),
        $config->addressField->uuid => [
            'street' => fake()->streetAddress(),
            'streetNumber' => fake()->buildingNumber(),
            'city' => fake()->city(),
            'zip' => fake()->postcode(),
        ],
        $config->phoneField->uuid => fake()->phoneNumber(),
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertEquals(1, Advice::count());
});

test('form can be created with seeder', function () {
    $this->artisan('db:seed --class=CreateAdviceForm');
    $this->assertTrue(true);
});
