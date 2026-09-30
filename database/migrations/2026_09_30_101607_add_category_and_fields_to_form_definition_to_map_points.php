<?php

declare(strict_types=1);

use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\MapPointCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points created from a form get a fixed category, or a sub category chosen through an option of a select
 * field in the form. The category fields are filled from form fields. Deleting is handled by the models,
 * so the foreign keys do not cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->foreignIdFor(MapPointCategory::class)->nullable()->constrained();
            $table->foreignIdFor(FormField::class, 'subcategory_field_id')->nullable()->constrained('form_fields');
        });

        Schema::create('form_definition_to_map_point_subcategories', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(FormDefinitionToMapPoint::class)->constrained(indexName: 'fdtmp_subcategories_mapping_foreign');
            $table->string('option_value');
            $table->foreignIdFor(MapPointCategory::class)->constrained(indexName: 'fdtmp_subcategories_category_foreign');
            $table->timestamps();

            $table->unique(['form_definition_to_map_point_id', 'option_value'], 'fdtmp_subcategories_option_unique');
        });

        Schema::create('form_definition_to_map_point_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(FormDefinitionToMapPoint::class)->constrained(indexName: 'fdtmp_fields_mapping_foreign');
            $table->foreignIdFor(FormField::class, 'target_field_id')->constrained('form_fields', indexName: 'fdtmp_fields_target_foreign');
            $table->foreignIdFor(FormField::class, 'source_field_id')->constrained('form_fields', indexName: 'fdtmp_fields_source_foreign');
            $table->timestamps();

            $table->unique(['form_definition_to_map_point_id', 'target_field_id'], 'fdtmp_fields_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_definition_to_map_point_fields');
        Schema::dropIfExists('form_definition_to_map_point_subcategories');

        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropForeign(['map_point_category_id']);
            $table->dropForeign(['subcategory_field_id']);
            $table->dropColumn(['map_point_category_id', 'subcategory_field_id']);
        });
    }
};
