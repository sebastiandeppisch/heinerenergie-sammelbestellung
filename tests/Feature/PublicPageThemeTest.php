<?php

use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the public form page shares the primary color of its initiative', function (): void {
    $group = Group::factory()->create([
        'primary_hue' => 180.5,
        'primary_lightness' => 0.65,
        'primary_chroma' => 0.18,
    ]);

    $formDefinition = FormDefinition::factory()->create(['group_id' => $group->id]);

    $response = $this->get(route('form.show', $formDefinition));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->where('theme.primaryHue', 180.5)
        ->where('theme.primaryLightness', 0.65)
        ->where('theme.primaryChroma', 0.18)
    );
});

test('the public form page falls back to the default color when the initiative has none', function (): void {
    $group = Group::factory()->create([
        'primary_hue' => null,
        'primary_lightness' => null,
        'primary_chroma' => null,
    ]);

    $formDefinition = FormDefinition::factory()->create(['group_id' => $group->id]);

    $response = $this->get(route('form.show', $formDefinition));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->where('theme.primaryHue', null)
        ->where('theme.primaryLightness', null)
        ->where('theme.primaryChroma', null)
    );
});

test('the public map shares the primary color of its initiative', function (): void {
    $group = Group::factory()->create([
        'primary_hue' => 210.5,
        'primary_lightness' => 0.6,
        'primary_chroma' => 0.15,
    ]);

    $mapEmbed = MapEmbed::factory()->create(['group_id' => $group->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->where('theme.primaryHue', 210.5)
        ->where('theme.primaryLightness', 0.6)
        ->where('theme.primaryChroma', 0.15)
    );
});

test('the public map falls back to the default color when the embed has no initiative', function (): void {
    $mapEmbed = MapEmbed::factory()->create(['group_id' => null]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->where('theme.primaryHue', null)
    );
});
