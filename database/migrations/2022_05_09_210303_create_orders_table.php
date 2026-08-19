<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();

            $table->string('firstName');
            $table->string('lastName');

            $table->string('street');
            $table->string('streetNumber');

            $table->unsignedInteger('zip');
            $table->string('city');

            $table->string('email');
            $table->string('phone');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}
