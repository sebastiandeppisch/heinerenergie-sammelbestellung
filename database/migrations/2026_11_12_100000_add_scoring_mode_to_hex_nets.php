<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hex_nets', function (Blueprint $table): void {
            $table->string('scoring_mode')->default('stones')->after('threshold_dark');
        });
    }

    public function down(): void
    {
        Schema::table('hex_nets', function (Blueprint $table): void {
            $table->dropColumn('scoring_mode');
        });
    }
};
