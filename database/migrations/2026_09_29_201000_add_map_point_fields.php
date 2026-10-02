<?php

declare(strict_types=1);

use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\MapPoint;
use App\Models\MapPointField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories define additional fields for their points through a form definition. Points store their
 * values as snapshots of the field, like checklist entries do, so values survive renamed or deleted fields.
 * Which fields are public is kept per field, so private contact data never reaches the public map.
 * Deleting is handled by the models, so the foreign keys do not cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->foreignIdFor(FormDefinition::class)->nullable()->unique()->after('parent_id')->constrained();
        });

        Schema::create('map_point_public_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(FormField::class)->unique()->constrained();
            $table->timestamps();
        });

        Schema::create('map_point_fields', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(MapPoint::class)->constrained();
            $table->foreignIdFor(FormField::class)->nullable()->constrained();
            $table->json('value')->nullable();
            $table->string('type');
            $table->string('label');
            $table->integer('sort_order');
            $table->timestamps();

            $table->unique(['map_point_id', 'form_field_id']);
        });

        Schema::create('map_point_field_options', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(MapPointField::class)->constrained();
            $table->string('label');
            $table->string('value');
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_point_field_options');
        Schema::dropIfExists('map_point_fields');
        Schema::dropIfExists('map_point_public_fields');

        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->dropForeign(['form_definition_id']);
            $table->dropUnique(['form_definition_id']);
            $table->dropColumn('form_definition_id');
        });
    }
};
