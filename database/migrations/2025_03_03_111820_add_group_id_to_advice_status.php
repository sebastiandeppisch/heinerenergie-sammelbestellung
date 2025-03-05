<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advice_status', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained('groups');
            $table->softDeletes();
        });

        Schema::create('advice_status_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('advice_status_id')->constrained('advice_status');
            $table->boolean('visible_in_group')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'advice_status_id']);
        });
    }

    public function down(): void
    {
        Schema::table('advice_status', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('advice_status_group');
        Schema::table('advice_status', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
