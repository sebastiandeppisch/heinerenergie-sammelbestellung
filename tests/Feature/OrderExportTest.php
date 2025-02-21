<?php

use App\Exports\OrdersExport;
use App\Models\BulkOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

uses()->beforeEach(function () {
    $user = User::factory()->admin()->create();
    $this->be($user);
    createBulkOrdersWithOrders();
    Excel::fake();
    app(SessionService::class)->actWithoutSelectingGroup();
});

function dumpBulkOrders()
{
    BulkOrder::all()->map(function (BulkOrder $bulkOrder) {
        echo '--- Bulkorder ';
        echo $bulkOrder->id.' - '.$bulkOrder->name."\n";
        echo "-Products-\n";
        $bulkOrder->products->map(function (Product $product) {
            echo '-'.$product->id.' - '.$product->name."\n";
        });
        echo "-Orders-\n";
        $bulkOrder->orders->map(function (Order $order) {
            echo '-'.$order->id.' - '.$order->name."\n";
            $order->orderItems->map(function (OrderItem $orderItem) {
                echo '--'.$orderItem->id.' - '.$orderItem->product->name."\n";
            });
        });

        echo "\n\n";
    });
}

function createBulkOrdersWithOrders()
{
    BulkOrder::truncate();
    Order::truncate();
    Product::truncate();
    BulkOrder::factory()->count(2)->create(['archived' => false]);

    BulkOrder::all()->map(function (BulkOrder $bulkOrder) {
        $a = Product::factory()->supplierProduct()->create(['bulk_order_id' => $bulkOrder->id]);
        $b = Product::factory()->ownProduct()->create(['bulk_order_id' => $bulkOrder->id]);
        foreach ([$a, $b] as $product) {
            Order::factory()
                ->has(OrderItem::factory()->for(
                    $product
                ))->create(['bulk_order_id' => $bulkOrder->id]);
        }
    });
}

test('order can not be exported by non admins', function () {
    $user = User::factory()->create();
    $this->be($user);

    $response = $this->get(route('orderexport', [
        'bulkorder' => BulkOrder::firstOrFail()->id,
    ]));

    $response->assertForbidden();
});

test('only the orders of the selected bulkorders are exported', function () {
    BulkOrder::all()->map(function (BulkOrder $bulkOrder) {
        $this->get(route('orderexport', [
            'bulkorder' => $bulkOrder->id,
            'filename' => $bulkOrder->id.'.xlsx',
        ]));

        Excel::assertDownloaded($bulkOrder->id.'.xlsx', function (OrdersExport $export) use ($bulkOrder) {
            $this->assertEquals(2, $export->collection()->count());
            $orderIds = $export->collection()->pluck('id')->sort()->values();
            $this->assertEquals(
                $bulkOrder->orders->pluck('id')->sort()->values(),
                $orderIds
            );

            return true;
        });
    });
})->skip();

test('when requested only the supplier products are exported', function () {
    $bulkOrder = BulkOrder::firstOrFail();
    $this->get(route('orderexport', [
        'bulkorder' => $bulkOrder->id,
        'filename' => $bulkOrder->id.'.xlsx',
        'products' => 'supplier',
    ]))->assertStatus(200);

    Excel::assertDownloaded($bulkOrder->id.'.xlsx', function (OrdersExport $export) {
        $this->assertEquals(1, $export->collection()->count());
        $order = Order::findOrFail($export->collection()->first()['id']);
        $product = $order->orderItems->first()->product;
        $this->assertTrue($product->is_supplier_product);
        $this->assertEquals($export->collection()->first()['product.name'], $product->name);

        return true;
    });
})->skip();

test('when requested only the non own products are exported', function () {
    $bulkOrder = BulkOrder::firstOrFail();
    $this->get(route('orderexport', [
        'bulkorder' => $bulkOrder->id,
        'filename' => $bulkOrder->id.'.xlsx',
        'products' => 'own',
    ]))->assertStatus(200);

    Excel::assertDownloaded($bulkOrder->id.'.xlsx', function (OrdersExport $export) {
        $this->assertEquals(1, $export->collection()->count());
        $order = Order::findOrFail($export->collection()->first()['id']);
        $product = $order->orderItems->first()->product;
        $this->assertFalse($product->is_supplier_product);
        $this->assertEquals($export->collection()->first()['product.name'], $product->name);

        return true;
    });
})->skip();

test('when requested all products are exported', function () {
    $bulkOrder = BulkOrder::firstOrFail();
    $this->get(route('orderexport', [
        'bulkorder' => $bulkOrder->id,
        'filename' => $bulkOrder->id.'.xlsx',
        'products' => 'all',
    ]))->assertStatus(200);

    Excel::assertDownloaded($bulkOrder->id.'.xlsx', function (OrdersExport $export) {
        $this->assertEquals(2, $export->collection()->count());

        return true;
    });
})->skip();
