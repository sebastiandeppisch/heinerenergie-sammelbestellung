<?php

use App\Models\FormDefinition;
use App\Models\FormField;
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
        Schema::create('form_definition_to_map_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignIdFor(FormDefinition::class)->constrained();

            $table->foreignIdFor(FormField::class, 'title_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'description_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'coordinate_field_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_definition_to_map_points');
    }
};
