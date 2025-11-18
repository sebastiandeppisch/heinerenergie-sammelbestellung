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
        Schema::table('form_definition_to_advice', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['advice_type_field_id']);
            // Make column nullable
            $table->unsignedBigInteger('advice_type_field_id')->nullable()->change();
            // Add new column
            $table->string('advice_type_direct')->nullable()->after('advice_type_field_id');
            // Re-add foreign key constraint
            $table->foreign('advice_type_field_id')->references('id')->on('form_fields');

            $table->string('advice_type_home_option_value')->nullable()->change();
            $table->string('advice_type_virtual_option_value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_definition_to_advice', function (Blueprint $table) {

            $table->string('advice_type_home_option_value')->nullable(false)->change();$table->string('advice_type_home_option_value')->nullable(false)->default(\App\Enums\AdviceType::Home->value)->change();
            $table->string('advice_type_virtual_option_value')->nullable(false)->default(\App\Enums\AdviceType::Virtual->value)->change();
            
            $table->dropColumn('advice_type_direct');
            // Drop foreign key constraint
            $table->dropForeign(['advice_type_field_id']);
            // Revert to not nullable (if needed)
            $table->unsignedBigInteger('advice_type_field_id')->nullable(false)->change();
            // Re-add foreign key constraint
            $table->foreign('advice_type_field_id')->references('id')->on('form_fields');
        });
    }
};
