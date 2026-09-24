<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Rolls back the migration that makes map data group-owned and removes the groups the
 * earlier migrations created, so each test starts from a database without groups.
 */
function rollBackMapPointGroupMigration(): void
{
    Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_09_11_121547_add_group_id_to_map_points_and_map_point_categories.php']);

    DB::table('advice_status')->delete();
    DB::table('group_user')->delete();
    DB::table('groups')->delete();
}

function insertMapDataWithoutGroup(): void
{
    $timestamps = ['created_at' => now(), 'updated_at' => now()];

    DB::table('map_point_categories')->insert(['uuid' => (string) Str::uuid(), 'name' => 'Alte Kategorie', ...$timestamps]);
    DB::table('map_points')->insert(['uuid' => (string) Str::uuid(), 'title' => 'Alter Punkt', 'published' => true, 'lat' => 49.87, 'lng' => 8.65, ...$timestamps]);
    DB::table('map_embeds')->insert(['uuid' => (string) Str::uuid(), 'lat' => 49.87, 'lng' => 8.65, 'zoom' => 12, ...$timestamps]);
}

/**
 * @return array<int, int>
 */
function mapDataGroupIds(): array
{
    return [
        ...DB::table('map_point_categories')->pluck('group_id')->all(),
        ...DB::table('map_points')->pluck('group_id')->all(),
        ...DB::table('map_embeds')->pluck('group_id')->all(),
    ];
}

test('the migration assigns existing map data to the first main group', function (): void {
    rollBackMapPointGroupMigration();

    $timestamps = ['created_at' => now(), 'updated_at' => now()];
    DB::table('groups')->insert(['id' => 5, 'uuid' => (string) Str::uuid(), 'name' => 'Hauptinitiative', ...$timestamps]);
    DB::table('groups')->insert(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Unterinitiative', 'parent_id' => 5, ...$timestamps]);
    DB::table('groups')->insert(['id' => 9, 'uuid' => (string) Str::uuid(), 'name' => 'Spätere Hauptinitiative', ...$timestamps]);
    insertMapDataWithoutGroup();

    Artisan::call('migrate');

    expect(mapDataGroupIds())->toBe([5, 5, 5]);
});

test('the migration creates a default group when map data exists but no group does', function (): void {
    rollBackMapPointGroupMigration();
    insertMapDataWithoutGroup();

    Artisan::call('migrate');

    $defaultGroup = DB::table('groups')->sole();

    expect($defaultGroup->name)->toBe('Standard Initiative')
        ->and(mapDataGroupIds())->toBe([$defaultGroup->id, $defaultGroup->id, $defaultGroup->id]);
});

test('the migration creates no group when there is no map data', function (): void {
    rollBackMapPointGroupMigration();

    Artisan::call('migrate');

    expect(DB::table('groups')->count())->toBe(0);
});
