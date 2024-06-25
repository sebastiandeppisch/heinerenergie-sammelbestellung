<?php

use App\Models\User;
use App\Models\Product;
use App\Models\BulkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->beforeEach(function () {
    $user = User::factory()->create();
    $this->be($user);
});

test('can be created', function () {
    $countBefore = BulkOrder::count();

    $response = $this->post('api/bulkorders', [
        'name' => 'Test',
        'archived' => true
    ]);

    $response->assertStatus(200);
    $this->assertEquals(1 + $countBefore, BulkOrder::count());
});

test('can be copied', function () {
    $old = BulkOrder::factory()->hasProductCategories(2)->hasProducts(3)->create();
    $this->assertEquals(3, $old->products()->count());

    $response = $this->post('api/bulkorders', [
        'name' => 'Test copied',
        'archived' => true,
        'copy_from' => $old->id
    ]);

    $response->assertStatus(200);

    $bulkOrder = BulkOrder::where('name', 'Test copied')->first();

    $this->assertEquals(2, $bulkOrder->productCategories()->count());
    $this->assertEquals(3, $bulkOrder->products()->count());
    $this->assertEquals(6, Product::count());
});
