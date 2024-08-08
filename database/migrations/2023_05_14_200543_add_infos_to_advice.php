<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->string('placeNotes')->nullable();
            $table->unsignedInteger('houseType')->nullable()->default(null);
            $table->boolean('helpType_place')->default(false);
            $table->boolean('helpType_technical')->default(false);
            $table->boolean('helpType_bureaucracy')->default(false);
            $table->boolean('helpType_other')->default(false);
            $table->boolean('landlordExists')->nullable()->default(null);
            //$table->enum('houseType', ['singleFamily', 'multiFamily', 'other'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('advices', function (Blueprint $table) {
            $table->dropColumn('placeNotes');
            $table->dropColumn('houseType');
            $table->dropColumn('helpType_place');
            $table->dropColumn('helpType_technical');
            $table->dropColumn('helpType_bureaucracy');
            $table->dropColumn('helpType_other');
            $table->dropColumn('landlordExists');
        });
    }
};
