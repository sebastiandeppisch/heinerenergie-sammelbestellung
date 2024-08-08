<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('duplicate products are combined', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $a = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id' => $order->id,
    ]);

    $b = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id' => $order->id,
    ]);

    $this->assertEquals(2, $order->fresh()->orderItems->count());

    $order->normalize();
    $this->assertEquals(1, $order->fresh()->orderItems->count());

    $this->assertEquals($a->quantity + $b->quantity, $order->fresh()->orderItems[0]->quantity);
});

test('zero quantity gets deleted', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 0,
    ]);

    $this->assertEquals(1, $order->fresh()->orderItems->count());

    $order->normalize();
    $this->assertEquals(0, $order->fresh()->orderItems->count());
});

test('items with unique product dont get deleted', function () {
    $order = Order::factory()->create();
    $a = OrderItem::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'order_id' => $order->id,
    ]);

    $b = OrderItem::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'order_id' => $order->id,
    ]);

    $this->assertEquals(2, $order->fresh()->orderItems->count());

    $order->normalize();
    $this->assertEquals(2, $order->fresh()->orderItems->count());
});
