<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_points', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamps();

            $table->decimal('lng', 10, 7)->nullable()->default(null);
            $table->decimal('lat', 10, 7)->nullable()->default(null);

            $table->string('title');
            $table->text('description')->nullable();

            $table->nullableMorphs('pointable');

            $table->boolean('published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_points');
    }
};
