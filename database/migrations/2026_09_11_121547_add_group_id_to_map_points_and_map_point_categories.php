<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Map points, categories and embeds become owned by a group (tenant).
 * Existing rows are assigned to the first main group. If there is no group yet
 * but rows exist, a default group is created, as done for advices before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable()->after('id');
        });

        Schema::table('map_points', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable()->after('id');
        });

        // The existing foreign key is dropped only to change the column to NOT NULL, it is re-added below.
        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
        });

        $this->assignOrphanedRowsToDefaultGroup();

        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
            $table->foreign('group_id')->references('id')->on('groups');
        });

        Schema::table('map_points', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
            $table->foreign('group_id')->references('id')->on('groups');
        });

        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
            $table->foreign('group_id')->references('id')->on('groups');
        });
    }

    public function down(): void
    {
        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('map_points', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
        });

        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable()->change();
            $table->foreign('group_id')->references('id')->on('groups');
        });
    }

    private function assignOrphanedRowsToDefaultGroup(): void
    {
        $hasOrphanedRows = DB::table('map_point_categories')->whereNull('group_id')->exists()
            || DB::table('map_points')->whereNull('group_id')->exists()
            || DB::table('map_embeds')->whereNull('group_id')->exists();

        if (! $hasOrphanedRows) {
            return;
        }

        $groupId = DB::table('groups')->whereNull('parent_id')->orderBy('id')->value('id')
            ?? DB::table('groups')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => 'Standard Initiative',
                'description' => 'Automatisch erstellte Standard-Initiative für bestehende Kartenpunkte',
                'accepts_transfers' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('map_point_categories')->whereNull('group_id')->update(['group_id' => $groupId]);
        DB::table('map_points')->whereNull('group_id')->update(['group_id' => $groupId]);
        DB::table('map_embeds')->whereNull('group_id')->update(['group_id' => $groupId]);
    }
};
