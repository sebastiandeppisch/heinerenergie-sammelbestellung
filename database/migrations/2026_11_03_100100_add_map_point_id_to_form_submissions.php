<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->foreignId('map_point_id')->nullable()->constrained('map_points')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('map_point_id');
        });
    }
};
