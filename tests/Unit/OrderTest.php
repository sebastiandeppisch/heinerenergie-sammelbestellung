<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('order can be shared', function () {
    $order = Order::factory()->create();
    $coAdvisor = User::factory()->create();

    $order->shares()->attach($coAdvisor->id);

    $this->assertTrue($coAdvisor->can('view', $order));
});

test('order can not be seen by other users', function () {
    $order = Order::factory()->create();
    $coAdvisor = User::factory()->create();

    $this->assertFalse($coAdvisor->can('view', $order));
});
