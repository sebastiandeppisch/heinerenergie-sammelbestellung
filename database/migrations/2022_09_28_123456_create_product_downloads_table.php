<?php

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_downloads', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('url');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');
        });

        foreach(Product::all() as $product) {
            if($product->url !== null) {
                $product->downloads()->create([
                    'name' => 'url',
                    'url' => $product->url
                ]);
            }
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('url')->nullable();
        });

        foreach(Product::all() as $product) {
            if($product->downloads()->count() > 0) {
                $product->url = $product->downloads()->first()->url;
                $product->save();
            }
        }
        Schema::dropIfExists('product_downloads');
    }
};
