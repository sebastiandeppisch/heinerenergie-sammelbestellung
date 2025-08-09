<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->timestamps();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('accepts_transfers')->default(true);
            $table->json('consulting_area')->nullable();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->foreignIdFor(Group::class, 'parent_id')->nullable()->constrained('groups');
        });

        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignIdFor(Group::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
