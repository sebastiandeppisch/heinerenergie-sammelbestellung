<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $defaultGroupId = DB::table('groups')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Standard Initiative',
            'description' => 'Automatisch erstellte Standard-Initiative für bestehende Beratungen',
            'accepts_transfers' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('advices', function (Blueprint $table) use ($defaultGroupId) {
            $table->foreignIdFor(Group::class)->default($defaultGroupId)->constrained();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->foreignIdFor(Group::class)->default(null)->change();
        });

        DB::table('advices')
            ->whereNull('group_id')
            ->update(['group_id' => $defaultGroupId]);

        $users = DB::table('users')->select('id', 'is_admin')->get();
        $groupUserRecords = $users->map(fn ($user) => [
            'group_id' => $defaultGroupId,
            'user_id' => $user->id,
            'is_admin' => $user->is_admin,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('group_user')->insert($groupUserRecords);

        DB::table('users')->update(['is_admin' => false]);
    }

    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn(['group_id']);
        });
    }
};
