<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories form a tree. Deleting a category is handled by the model, which moves its
 * sub categories and points up to its parent, so the foreign key does not cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('group_id')->constrained('map_point_categories');
        });
    }

    public function down(): void
    {
        Schema::table('map_point_categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
