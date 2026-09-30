<?php

use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('related advices with same email list advisor, status and group and mark which ones are viewable', function (): void {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['first_name' => 'Erika', 'last_name' => 'Muster']);
    $ownGroup = Group::create(['name' => 'Own Group', 'description' => 'Own']);
    $otherGroup = Group::create(['name' => 'Other Group', 'description' => 'Other']);
    $status = AdviceStatus::factory()->create(['name' => 'In Bearbeitung', 'group_id' => $ownGroup->id]);

    $advice = Advice::factory()->create(['email' => 'Same@example.com', 'advisor_id' => $user->id, 'group_id' => $ownGroup->id]);
    $own = Advice::factory()->create(['email' => 'same@example.com', 'advisor_id' => $user->id, 'group_id' => $ownGroup->id]);
    $ofColleague = Advice::factory()->create([
        'email' => 'same@example.com',
        'advisor_id' => $colleague->id,
        'advice_status_id' => $status->id,
        'group_id' => $ownGroup->id,
    ]);
    $unassignedElsewhere = Advice::factory()->create(['email' => 'same@example.com', 'group_id' => $otherGroup->id, 'advisor_id' => null]);
    Advice::factory()->create(['email' => 'different@example.com', 'advisor_id' => $user->id]);

    $result = app(AdviceService::class)->getRelatedAdvicesByEmail($advice, $user);

    expect($result)->toHaveCount(3);

    $byId = collect($result)->keyBy('id');

    expect($byId[$own->uuid]['can_view'])->toBeTrue()
        ->and($byId[$ofColleague->uuid]['can_view'])->toBeFalse()
        ->and($byId[$ofColleague->uuid]['advisor_name'])->toBe('Erika Muster')
        ->and($byId[$ofColleague->uuid]['status_name'])->toBe('In Bearbeitung')
        ->and($byId[$ofColleague->uuid]['group_name'])->toBe('Own Group')
        ->and($byId[$unassignedElsewhere->uuid]['can_view'])->toBeFalse()
        ->and($byId[$unassignedElsewhere->uuid]['advisor_name'])->toBeNull()
        ->and($byId[$unassignedElsewhere->uuid]['group_name'])->toBe('Other Group');
});

test('advices without email have no related advices', function (): void {
    $user = User::factory()->create();
    $advice = Advice::factory()->create(['email' => '', 'advisor_id' => $user->id]);
    Advice::factory()->create(['email' => '', 'advisor_id' => $user->id]);

    expect(app(AdviceService::class)->getRelatedAdvicesByEmail($advice, $user))->toBe([]);
});
