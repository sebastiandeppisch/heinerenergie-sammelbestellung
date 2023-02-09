<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\BulkOrder;
use App\Models\OrderItem;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;

class StoreOrderController extends Controller
{
    public function __invoke(StoreOrderRequest $request)
    {
        $advisor = User::where('email', $request->advisorEmail)->firstOrFail();
        $order = new Order($request->all());
        $order->advisor_id = $advisor->id;
        $order->bulk_order_id = BulkOrder::getCurrentBulkOrder()->id;
        $order->save();

        foreach($request->orderItems as $orderItem){
            $quantity = $orderItem["quantity"];
            if($quantity <= 0){
                continue;
            }
            $item = new OrderItem();
            $item->product_id = $orderItem["product"]["id"];
            $item->quantity = $quantity;
            $item->order_id = $order->id;
            $item->save();
        }
        $order =  Order::with('orderItems')->findOrFail($order->id);
        $order->orderItems->each(function(OrderItem $orderItem){
            $orderItem->load('product');
        });

        OrderCreated::dispatch($order);

        return $order;
    }
}
