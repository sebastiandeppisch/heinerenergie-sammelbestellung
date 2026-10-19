<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->string('contact_email')->nullable();
            $table->timestamp('contact_email_verified_at')->nullable();
            $table->boolean('contact_enabled')->default(false);
            $table->string('contact_verification_token_hash', 64)->nullable()->unique();
            $table->timestamp('contact_verification_expires_at')->nullable();
            $table->string('edit_token_hash', 64)->nullable()->unique();
            $table->timestamp('edit_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('map_points', function (Blueprint $table): void {
            $table->dropUnique(['contact_verification_token_hash']);
            $table->dropUnique(['edit_token_hash']);
            $table->dropColumn([
                'contact_email',
                'contact_email_verified_at',
                'contact_enabled',
                'contact_verification_token_hash',
                'contact_verification_expires_at',
                'edit_token_hash',
                'edit_token_expires_at',
            ]);
        });
    }
};
