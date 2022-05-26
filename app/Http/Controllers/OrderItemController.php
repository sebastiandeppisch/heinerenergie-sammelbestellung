<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;

class OrderItemController extends Controller
{
    public function index(Order $order)
    {
        return $order->orderItems;
    }

    public function store(Order $order, StoreOrderItemRequest $request)
    {
        $orderItem = new OrderItem($request->all());
        $orderItem->order_id = $order->id;
        $orderItem->save();
        $order->normalize();
        return $orderItem;
    }

    public function update(Order $order, OrderItem $orderitem, UpdateOrderItemRequest $request)
    {
        $orderitem->fill($request->all());
        $orderitem->save();
        $order->normalize();
        return $orderitem;
    }

    public function destroy(Order $order, OrderItem $orderitem)
    {
        $orderitem->delete();
    }

    public function show(Order $order, OrderItem $orderitem){
        return $orderitem;
    }
}
