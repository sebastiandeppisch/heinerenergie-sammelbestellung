<?php

use App\Models\MapPoint;
use App\Models\FormDefinitionToMapPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('can be created by factory', function () {
    FormDefinitionToMapPoint::factory()->create();
    $this->assertTrue(true);
});

test('submitting the form produces a map point', function () {

    $this->withoutExceptionHandling();

    $config = FormDefinitionToMapPoint::factory()->create();

    $form = $config->formDefinition;

    $response = $this->post(route('form.submit', $form), [
        $config->titleField->id => fake()->sentence(3),
        $config->descriptionField->id => fake()->sentence(10), // Shorter description
        $config->coordinateField->id => [
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertEquals(1, MapPoint::count());

    $mapPoint = MapPoint::first();
    $this->assertFalse($mapPoint->published); // should be unpublished initially
});

test('submitting the form fires map point created event', function () {
    Event::fake();

    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;

    $this->post(route('form.submit', $form), [
        $config->titleField->id => 'Test Titel',
        $config->descriptionField->id => 'Test Beschreibung',
        $config->coordinateField->id => [
            'lat' => 49.8728,
            'lng' => 8.6510,
        ],
    ]);

    Event::assertDispatched(\App\Events\MapPointCreatedByFormSubmission::class);
});


test('form can be created with seeder', function () {
    $this->artisan('db:seed --class=CreateMapPointForm');
    $this->assertTrue(true);
});
