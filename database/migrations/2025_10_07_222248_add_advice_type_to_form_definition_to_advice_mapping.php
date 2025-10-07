<?php

use App\Enums\AdviceType;
use App\Models\FormFieldOption;
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
            $table->string('advice_type_home_option_value')->default(AdviceType::Home->value);
            $table->string('advice_type_virtual_option_value')->default(AdviceType::Virtual->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_definition_to_advice', function (Blueprint $table) {
            $table->dropColumn(['advice_type_home_option_value', 'advice_type_virtual_option_value']);
        });
    }
};
