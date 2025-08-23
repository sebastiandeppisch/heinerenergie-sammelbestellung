<?php

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
        Schema::table('advices', function (Blueprint $table) {
            $table->renameColumn('firstName', 'first_name');
            $table->renameColumn('lastName', 'last_name');
            $table->renameColumn('streetNumber', 'street_number');
            $table->renameColumn('placeNotes', 'place_notes');
            $table->renameColumn('houseType', 'house_type');
            $table->renameColumn('helpType_place', 'help_type_place');
            $table->renameColumn('helpType_technical', 'help_type_technical');
            $table->renameColumn('helpType_bureaucracy', 'help_type_bureaucracy');
            $table->renameColumn('helpType_other', 'help_type_other');
            $table->renameColumn('landlordExists', 'landlord_exists');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('streetNumber', 'street_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->renameColumn('first_name', 'firstName');
            $table->renameColumn('last_name', 'lastName');
            $table->renameColumn('street_number', 'streetNumber');
            $table->renameColumn('place_notes', 'placeNotes');
            $table->renameColumn('house_type', 'houseType');
            $table->renameColumn('help_type_place', 'helpType_place');
            $table->renameColumn('help_type_technical', 'helpType_technical');
            $table->renameColumn('help_type_bureaucracy', 'helpType_bureaucracy');
            $table->renameColumn('help_type_other', 'helpType_other');
            $table->renameColumn('landlord_exists', 'landlordExists');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('street_number', 'streetNumber');
        });
    }
};
