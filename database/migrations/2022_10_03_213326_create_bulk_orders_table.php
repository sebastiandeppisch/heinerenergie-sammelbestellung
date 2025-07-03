<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('bulk_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->boolean('archived')->default(false);
        });

        $bulkOrderId = DB::table('bulk_orders')->insertGetId([
            'name' => 'Standard Sammelbestellung',
        ]);

        Schema::table('orders', function (Blueprint $table) use ($bulkOrderId) {
            $table->foreignId('bulk_order_id')
                ->default($bulkOrderId)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('bulk_order_id')
                ->nullable(false)
                ->default(null)
                ->change();
        });

        Schema::table('products', function (Blueprint $table) use ($bulkOrderId) {
            $table->foreignId('bulk_order_id')
                ->default($bulkOrderId)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('bulk_order_id')
                ->nullable(false)
                ->default(null)
                ->change();
        });

        Schema::table('product_categories', function (Blueprint $table) use ($bulkOrderId) {
            $table->foreignId('bulk_order_id')
                ->default($bulkOrderId)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreignId('bulk_order_id')
                ->nullable(false)
                ->default(null)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['bulk_order_id']);
            $table->dropColumn('bulk_order_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['bulk_order_id']);
            $table->dropColumn('bulk_order_id');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['bulk_order_id']);
            $table->dropColumn('bulk_order_id');
        });
        Schema::drop('bulk_orders');
    }
};
