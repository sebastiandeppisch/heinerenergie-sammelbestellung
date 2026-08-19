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
        Schema::table('groups', function (Blueprint $table): void {
            $table->float('primary_hue')->nullable();
            $table->float('primary_lightness')->nullable()->after('primary_hue');
            $table->float('primary_chroma')->nullable()->after('primary_lightness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn(['primary_hue', 'primary_lightness', 'primary_chroma']);
        });
    }
};
