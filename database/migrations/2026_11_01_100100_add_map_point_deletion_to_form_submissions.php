<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->timestamp('map_point_deleted_at')->nullable();
            $table->string('map_point_deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropColumn(['map_point_deleted_at', 'map_point_deleted_by']);
        });
    }
};
