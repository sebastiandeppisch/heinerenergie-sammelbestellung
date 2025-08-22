<?php

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
        Schema::table('advices', function (Blueprint $table) {
            $table->string('zip')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('zip')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->unsignedInteger('zip');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('zip');
        });
    }
};
