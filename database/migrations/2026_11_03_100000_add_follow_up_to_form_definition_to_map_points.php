<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array REQUIRED_FIELD_COLUMNS = ['title_field_id', 'description_field_id', 'coordinate_field_id'];

    public function up(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->string('mode')->default('create')->after('form_definition_id');
            $table->foreignId('follow_up_form_definition_id')->nullable()->constrained('form_definitions')->nullOnDelete();
            $table->string('follow_up_button_text')->nullable();
        });

        // A form that adds to an existing point does not ask for title, description and location.
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            foreach (self::REQUIRED_FIELD_COLUMNS as $column) {
                $table->dropForeign([$column]);
                $table->unsignedBigInteger($column)->nullable()->change();
                $table->foreign($column)->references('id')->on('form_fields');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('follow_up_form_definition_id');
            $table->dropColumn(['mode', 'follow_up_button_text']);
        });
    }
};
