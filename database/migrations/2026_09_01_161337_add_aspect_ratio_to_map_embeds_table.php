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
        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->unsignedTinyInteger('aspect_ratio_width')->default(16);
            $table->unsignedTinyInteger('aspect_ratio_height')->default(9);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->dropColumn(['aspect_ratio_width', 'aspect_ratio_height']);
        });
    }
};
