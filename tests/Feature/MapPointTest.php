<?php

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with a factory', function () {
    MapPoint::factory()->create();
    MapPoint::factory()->withRandomOrNullPointable()->create();
    $this->assertTrue(true);
});

it('can be created with a form submission', function () {
    MapPoint::factory()
        ->withFormSubmission()->create();
    expect(MapPoint::firstOrFail()->pointable->is(FormSubmission::firstOrFail()))->toBeTrue();
});

it('can be created with a advice', function () {
    MapPoint::factory()->withAdvice()->create();
    expect(MapPoint::firstOrFail()->pointable->is(Advice::firstOrFail()))->toBeTrue();
});
