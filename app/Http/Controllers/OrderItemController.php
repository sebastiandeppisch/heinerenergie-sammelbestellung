<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\StoreOrderItemRequest;

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
    }

    public function update(Order $order, OrderItem $orderitem, UpdateProductRequest $request)
    {
        $orderitem->fill($request->all());
        $orderitem->save();
        $order->normalize();
    }

    public function destroy(Order $order, OrderItem $orderitem)
    {
        $orderitem->delete();
    }
}
