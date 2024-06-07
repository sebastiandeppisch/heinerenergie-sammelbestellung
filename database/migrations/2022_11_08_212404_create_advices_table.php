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
        Schema::create('advice_status', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name');
        });

        Schema::create('advices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('firstName');
            $table->string('lastName');

            $table->string('street');
            $table->string('streetNumber');

            $table->unsignedInteger('zip');
            $table->string('city');

            $table->decimal('long', 10, 7)->nullable()->default(null);
            $table->decimal('lat', 10, 7)->nullable()->default(null);

            $table->string('email');
            $table->string('phone');

            $table->text('commentary')->nullable();

            $table->foreignId('advisor_id')->nullable();
            $table->foreign('advisor_id')->references('id')->on('users');

            $table->foreignId('advice_status_id')->nullable();
            $table->foreign('advice_status_id')->references('id')->on('advice_status');

        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('long', 10, 7)->nullable()->default(null);
            $table->decimal('lat', 10, 7)->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('advices');
        Schema::dropIfExists('advice_status');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['long', 'lat']);
        });
    }
};
