<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_definitions', function (Blueprint $table): void {
            $table->boolean('requires_email_confirmation')->default(false)->after('next_form_button_text');
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('submitted_at');
            $table->string('confirmation_token_hash', 64)->nullable()->unique()->after('confirmed_at');
            $table->timestamp('confirmation_expires_at')->nullable()->after('confirmation_token_hash');
        });

        Schema::table('form_definition_to_advice', function (Blueprint $table): void {
            $table->foreignId('condition_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_definition_to_advice', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('condition_field_id');
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropUnique(['confirmation_token_hash']);
            $table->dropColumn(['confirmed_at', 'confirmation_token_hash', 'confirmation_expires_at']);
        });

        Schema::table('form_definitions', function (Blueprint $table): void {
            $table->dropColumn('requires_email_confirmation');
        });
    }
};
