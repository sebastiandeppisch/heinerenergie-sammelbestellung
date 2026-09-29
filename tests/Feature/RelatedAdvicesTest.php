<?php

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('related advices with same email are split into visible and hidden', function (): void {
    $user = User::factory()->create();
    $otherGroup = Group::create(['name' => 'Other Group', 'description' => 'Other']);

    $advice = Advice::factory()->create(['email' => 'Same@example.com', 'advisor_id' => $user->id]);
    $own = Advice::factory()->create(['email' => 'same@example.com', 'advisor_id' => $user->id]);
    Advice::factory()->create(['email' => 'same@example.com', 'group_id' => $otherGroup->id, 'advisor_id' => null]);
    Advice::factory()->create(['email' => 'different@example.com', 'advisor_id' => $user->id]);

    $result = app(AdviceService::class)->getRelatedAdvicesByEmail($advice, $user);

    expect($result['visible'])->toHaveCount(1)
        ->and($result['visible'][0]['id'])->toBe($own->uuid)
        ->and($result['hidden_count'])->toBe(1);
});
