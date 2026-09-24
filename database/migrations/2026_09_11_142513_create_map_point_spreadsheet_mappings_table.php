<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_point_spreadsheet_mappings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('columns');
            $table->string('key_field');
            $table->timestamps();

            $table->unique(['group_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_point_spreadsheet_mappings');
    }
};
