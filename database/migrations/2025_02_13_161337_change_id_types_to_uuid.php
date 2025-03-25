<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropForeignKeys()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_advisor_id_foreign');
        });

        Schema::table('sharings', function (Blueprint $table) {
            $table->dropForeign('sharings_advisor_id_foreign');
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->dropForeign('advices_advisor_id_foreign');
            $table->dropForeign('advices_advice_status_id_foreign');
        });
    }

    private function changeIdTypes()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
            $table->uuid('advice_status_id')->change();
            $table->uuid('id')->change();
        });

        Schema::table('sharings', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
            $table->uuid('sharing_id')->change();
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
        });
    }

    private function createUuids()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
            $table->uuid('advice_status_id')->change();
            $table->uuid('id')->change();
        });

        Schema::table('sharings', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
            $table->uuid('sharing_id')->change();
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('advisor_id')->change();
        });
    }

    private function recreateForeignKeys()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('advisor_id')->references('id')->on('users')->constrained();
        });

        Schema::table('sharings', function (Blueprint $table) {
            $table->foreign('advisor_id')->references('id')->on('users')->constrained();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->foreign('advisor_id')->references('id')->on('users')->constrained();
            $table->foreign('advice_status_id')->references('id')->on('advice_status')->constrained();
        });
    }

    public function up(): void
    {
        if (Schema::getColumnType('users', 'id') !== 'bigint') {
            //newly created database, nothing to change
            return;
        }

        $this->dropForeignKeys();

        $this->changeIdTypes();

        $this->createUuids();

        $this->recreateForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert UUIDs back to integers/bigIntegers
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('id')->change();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->bigInteger('id')->change();
            $table->bigInteger('advisor_id')->change();
        });

        Schema::table('sharings', function (Blueprint $table) {
            $table->bigInteger('id')->change();
            $table->bigInteger('advisor_id')->change();
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->bigInteger('id')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('id')->change();
            $table->bigInteger('advisor_id')->change();
        });

        // Revert other tables if added in the up() method

        // Recreate original foreign keys if needed
    }
};
