<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advices', function (Blueprint $table): void {
            $table->string('geocoding_status')->nullable()->after('lng');
        });

        // Existing rows with coordinates are done. Rows without stay null for
        // "unknown" rather than pending, so the catch up command does not fire
        // thousands of requests at OpenStreetMap on the first run.
        DB::table('advices')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->update(['geocoding_status' => 'success']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table): void {
            $table->dropColumn('geocoding_status');
        });
    }
};
