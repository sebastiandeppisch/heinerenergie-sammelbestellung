<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->string('public_consent')->default('not_required')->after('published');
            $table->timestamp('public_consent_at')->nullable()->after('public_consent');
            $table->text('public_consent_text')->nullable()->after('public_consent_at');
            $table->json('public_consent_fields')->nullable()->after('public_consent_text');
        });
    }

    public function down(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->dropColumn([
                'public_consent',
                'public_consent_at',
                'public_consent_text',
                'public_consent_fields',
            ]);
        });
    }
};
