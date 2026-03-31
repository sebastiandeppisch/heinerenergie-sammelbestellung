<?php

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with a factory', function (): void {
    MapPoint::factory()->create();
    MapPoint::factory()->withRandomOrNullPointable()->create();
    $this->assertTrue(true);
});

it('can be created with a form submission', function (): void {
    MapPoint::factory()
        ->withFormSubmission()->create();
    $this->assertEquals(FormSubmission::firstOrFail()->id, MapPoint::firstOrFail()->pointable()->first()->id);
});

it('can be created with a advice', function (): void {
    MapPoint::factory()->withAdvice()->create();
    $this->assertEquals(Advice::firstOrFail()->id, MapPoint::firstOrFail()->pointable()->first()->id);
});
