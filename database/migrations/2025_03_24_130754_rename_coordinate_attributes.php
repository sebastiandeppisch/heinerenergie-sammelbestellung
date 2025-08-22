<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('long', 'lng');
            // $table->renameColumn('lat', 'lat');
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->renameColumn('long', 'lng');
            // $table->renameColumn('lat', 'lat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('lng', 'long');
            // $table->renameColumn('lat', 'lat');
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->renameColumn('lng', 'long');
            // $table->renameColumn('lat', 'lat');
        });
    }
};
