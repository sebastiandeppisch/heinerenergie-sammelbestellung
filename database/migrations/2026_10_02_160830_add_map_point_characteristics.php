<?php

declare(strict_types=1);

use App\Models\FormDefinition;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Characteristics ("Maßnahmen" in the UI) are optional extras of a point, e.g. a hedgehog gate. Each belongs to one
 * category and can be chosen for points of that category and all its sub categories. A characteristic can have
 * own fields, which points get in addition to the fields of their category. Forms pick characteristics through
 * the options of a checkbox field. Deleting is handled by the models, so the foreign keys do not cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_point_characteristics', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignIdFor(MapPointCategory::class)->constrained();
            $table->string('name');
            $table->string('icon_path')->nullable();
            $table->string('color')->nullable();
            $table->foreignIdFor(FormDefinition::class)->nullable()->unique()->constrained();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('map_point_characteristic_map_point', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(MapPoint::class)->constrained();
            $table->foreignIdFor(MapPointCharacteristic::class)->constrained(indexName: 'mpc_map_point_characteristic_foreign');
            $table->timestamps();

            $table->unique(['map_point_id', 'map_point_characteristic_id'], 'mpc_map_point_unique');
        });

        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->foreignIdFor(FormField::class, 'characteristics_field_id')->nullable()->constrained('form_fields');
        });

        Schema::create('form_definition_to_map_point_characteristics', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(FormDefinitionToMapPoint::class)->constrained(indexName: 'fdtmp_characteristics_mapping_foreign');
            $table->string('option_value');
            $table->foreignIdFor(MapPointCharacteristic::class)->constrained(indexName: 'fdtmp_characteristics_characteristic_foreign');
            $table->timestamps();

            $table->unique(['form_definition_to_map_point_id', 'option_value'], 'fdtmp_characteristics_option_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_definition_to_map_point_characteristics');

        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropForeign(['characteristics_field_id']);
            $table->dropColumn('characteristics_field_id');
        });

        Schema::dropIfExists('map_point_characteristic_map_point');
        Schema::dropIfExists('map_point_characteristics');
    }
};
