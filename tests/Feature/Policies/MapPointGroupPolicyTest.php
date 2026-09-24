<?php

use App\Context\GroupContext;
use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointSpreadsheetMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->root = Group::factory()->create();
    $this->child = Group::factory()->create(['parent_id' => $this->root->id]);
    $this->sibling = Group::factory()->create(['parent_id' => $this->root->id]);
});

function userActingInGroup(Group $group, bool $asGroupAdmin): User
{
    $user = User::factory()->create(['is_admin' => false]);
    app()->instance(GroupContextContract::class, new GroupContext($group, false, $user, $asGroupAdmin));

    return $user;
}

test('group admins may only change map data owned by their group or its descendants', function (string $actingGroup, string $owningGroup, bool $expected): void {
    $admin = userActingInGroup($this->{$actingGroup}, asGroupAdmin: true);
    $owner = $this->{$owningGroup};

    $mapPoint = MapPoint::factory()->for($owner)->create();
    $category = MapPointCategory::factory()->for($owner)->create();
    $mapEmbed = MapEmbed::factory()->for($owner)->create();
    $importMapping = MapPointSpreadsheetMapping::factory()->for($owner)->create();

    expect($admin->can('update', $mapPoint))->toBe($expected)
        ->and($admin->can('delete', $mapPoint))->toBe($expected)
        ->and($admin->can('update', $category))->toBe($expected)
        ->and($admin->can('delete', $category))->toBe($expected)
        ->and($admin->can('update', $mapEmbed))->toBe($expected)
        ->and($admin->can('delete', $mapEmbed))->toBe($expected)
        ->and($admin->can('update', $importMapping))->toBe($expected)
        ->and($admin->can('delete', $importMapping))->toBe($expected);
})->with([
    'own group' => ['child', 'child', true],
    'descendant group' => ['root', 'child', true],
    'ancestor group' => ['child', 'root', false],
    'sibling group' => ['child', 'sibling', false],
]);

test('only group admins may list, create, import and export map points', function (bool $asGroupAdmin): void {
    $user = userActingInGroup($this->child, $asGroupAdmin);

    expect($user->can('viewAny', MapPoint::class))->toBe($asGroupAdmin)
        ->and($user->can('create', MapPoint::class))->toBe($asGroupAdmin)
        ->and($user->can('import', MapPoint::class))->toBe($asGroupAdmin)
        ->and($user->can('export', MapPoint::class))->toBe($asGroupAdmin)
        ->and($user->can('create', MapPointSpreadsheetMapping::class))->toBe($asGroupAdmin);
})->with([
    'group admin' => true,
    'group member' => false,
]);
