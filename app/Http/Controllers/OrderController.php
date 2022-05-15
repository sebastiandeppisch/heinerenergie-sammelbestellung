<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Mail\OrderCreated;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Order::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
    {
        $order = new Order($request->all());
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

        Mail::to($order->email)->send(new OrderCreated($order));

        return $order;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $order->load('orderItems');
        $order->orderItems->each(function(OrderItem $orderItem){
            $orderItem->load('product');
        });
        return $order;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order->fill($request->all());
        $order->save();
        return $order;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }

    public function validateorderform(StoreOrderRequest $request){
    }
    public function validateEditOrderForm(UpdateOrderRequest $request){
    }

    public function export(){
        $date = Carbon::now()->format('Y-m-d H:i:s');
        $filename = sprintf("%s Bestellungen heinerenergie.xlsx", $date);
        return Excel::download(new OrdersExport, $filename);
    }
}
