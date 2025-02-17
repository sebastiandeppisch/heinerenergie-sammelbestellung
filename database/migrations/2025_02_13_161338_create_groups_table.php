<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->foreignIdFor(Group::class, 'parent_id')->nullable()->constrained('groups');
            $table->boolean('accepts_transfers')->default(true);
        });

        Schema::create('group_user', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Group::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->foreignIdFor(Group::class, 'group_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
