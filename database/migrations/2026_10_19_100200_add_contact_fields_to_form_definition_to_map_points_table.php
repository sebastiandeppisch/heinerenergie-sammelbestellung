<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->foreignId('contact_email_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
            $table->foreignId('contact_enabled_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contact_email_field_id');
            $table->dropConstrainedForeignId('contact_enabled_field_id');
        });
    }
};
