<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hex_nets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('cta_text')->nullable();
            $table->json('play_area')->nullable();
            $table->decimal('grid_origin_lat', 10, 7)->nullable();
            $table->decimal('grid_origin_lng', 10, 7)->nullable();
            $table->unsignedInteger('hex_radius_m')->default(150);
            $table->boolean('grid_locked')->default(false);
            $table->unsignedInteger('threshold_medium')->default(2);
            $table->unsignedInteger('threshold_dark')->default(3);
            $table->string('color_open', 7)->default('#e4e9e2');
            $table->string('color_light', 7)->default('#b7e4a8');
            $table->string('color_medium', 7)->default('#52b45a');
            $table->string('color_dark', 7)->default('#1f7a34');
            $table->unsignedInteger('jitter_m')->default(40);
            $table->boolean('auto_publish')->default(false);
            $table->foreignId('form_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hex_net_map_point_category', function (Blueprint $table): void {
            $table->foreignId('hex_net_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_point_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['hex_net_id', 'map_point_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hex_net_map_point_category');
        Schema::dropIfExists('hex_nets');
    }
};
