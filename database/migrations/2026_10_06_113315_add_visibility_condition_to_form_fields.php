<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A field can be shown only when an earlier option field of the form has a certain option, e.g. the fields of a
 * characteristic only when it is checked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table): void {
            $table->foreignId('visible_if_field_id')->nullable()->after('max_images')->constrained('form_fields');
            $table->string('visible_if_option_value')->nullable()->after('visible_if_field_id');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('visible_if_field_id');
            $table->dropColumn('visible_if_option_value');
        });
    }
};
