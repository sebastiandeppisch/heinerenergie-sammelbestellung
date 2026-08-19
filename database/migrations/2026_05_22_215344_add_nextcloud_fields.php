<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advices', function (Blueprint $table): void {
            $table->string('nextcloud_folder_id')->nullable();
            $table->string('nextcloud_folder_path')->nullable();
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->string('nextcloud_search_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table): void {
            $table->dropColumn(['nextcloud_folder_id', 'nextcloud_folder_path']);
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn('nextcloud_search_path');
        });
    }
};
