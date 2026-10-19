<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->string('contact_email')->nullable()->after('location');
            $table->string('public_consent')->default('not_required')->after('published');
            $table->timestamp('public_consent_at')->nullable()->after('public_consent');
            $table->text('public_consent_text')->nullable()->after('public_consent_at');
            $table->json('public_consent_fields')->nullable()->after('public_consent_text');
            $table->string('edit_token_hash', 64)->nullable()->unique()->after('public_consent_fields');
            $table->timestamp('edit_token_expires_at')->nullable()->after('edit_token_hash');
            $table->index('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->dropIndex(['contact_email']);
            $table->dropUnique(['edit_token_hash']);
            $table->dropColumn([
                'contact_email',
                'public_consent',
                'public_consent_at',
                'public_consent_text',
                'public_consent_fields',
                'edit_token_hash',
                'edit_token_expires_at',
            ]);
        });
    }
};
