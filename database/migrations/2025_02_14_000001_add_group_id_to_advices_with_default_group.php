<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('advices', function (Blueprint $table) {
            $table->foreignIdFor(Group::class)->constrained();
        });

        $defaultGroupId = DB::table('groups')->insertGetId([
            'name' => 'Standard Initiative',
            'description' => 'Automatisch erstellte Standard-Initiative für bestehende Beratungen',
            'accepts_transfers' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('advices')
            ->whereNull('group_id')
            ->update(['group_id' => $defaultGroupId]);

        $users = DB::table('users')->select('id', 'is_admin')->get();
        $groupUserRecords = $users->map(function ($user) use ($defaultGroupId) {
            return [
                'group_id' => $defaultGroupId,
                'user_id' => $user->id,
                'is_admin' => $user->is_admin,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('group_user')->insert($groupUserRecords);

        DB::table('users')->update(['is_admin' => false]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
    }
};
