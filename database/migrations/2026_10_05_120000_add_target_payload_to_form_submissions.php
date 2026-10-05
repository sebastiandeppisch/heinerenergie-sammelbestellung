<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->json('target_payload')->nullable()->after('confirmation_expires_at');
            $table->timestamp('targets_failed_at')->nullable()->after('target_payload');
            $table->text('targets_error')->nullable()->after('targets_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropColumn(['target_payload', 'targets_failed_at', 'targets_error']);
        });
    }
};
