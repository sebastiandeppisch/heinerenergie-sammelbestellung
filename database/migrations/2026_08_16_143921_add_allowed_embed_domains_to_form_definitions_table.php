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
        Schema::table('form_definitions', function (Blueprint $table): void {
            $table->json('allowed_embed_domains')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_definitions', function (Blueprint $table): void {
            $table->dropColumn('allowed_embed_domains');
        });
    }
};
