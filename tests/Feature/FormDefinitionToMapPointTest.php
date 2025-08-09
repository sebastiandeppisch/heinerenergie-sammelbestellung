<?php

use App\Models\FormDefinitionToMapPoint;
use App\Models\MapPoint;
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
        $config->titleField->uuid => fake()->sentence(3),
        $config->descriptionField->uuid => fake()->sentence(10), // Shorter description
        $config->coordinateField->uuid => [
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
    $this->withoutExceptionHandling();
    $config = FormDefinitionToMapPoint::factory()->create();
    Event::fake();

    $form = $config->formDefinition;

    $this->post(route('form.submit', $form), [
        $config->titleField->uuid => 'Test Titel',
        $config->descriptionField->uuid => 'Test Beschreibung',
        $config->coordinateField->uuid => [
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
