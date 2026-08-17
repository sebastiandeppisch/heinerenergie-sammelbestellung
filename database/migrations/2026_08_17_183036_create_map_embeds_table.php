<?php

use App\Models\MapEmbed;
use App\Models\MapPointCategory;
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
        Schema::create('map_embeds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedTinyInteger('zoom');
            $table->boolean('show_table')->default(true);
            $table->timestamps();
        });

        Schema::create('map_embed_map_point_category', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MapEmbed::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MapPointCategory::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_embed_map_point_category');
        Schema::dropIfExists('map_embeds');
    }
};
