<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\BulkOrder;
use App\Models\OrderItem;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\RequireOrderPassword;
use App\Mail\OrderChecking;
use Illuminate\Validation\ValidationException;
use Doctrine\Common\Cache\Psr6\InvalidArgument;

class OrderController extends Controller
{

    public function __construct(){
        //$this->authorizeResource(Order::class, 'order');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Bulkorder $bulkorder)
    {
        if($bulkorder->id !== null){
            $orders =  $bulkorder->orders;
        }else{
            $orders = Order::all();
        }

        return $orders->filter(function(Order $order){
            return Auth::user()->can('view', $order);
        })->values();    
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
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
        $order->checked = false;
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
        if($order->archived){
            throw new InvalidArgument("An archived order can not be changed");
        }
        foreach($order->orderItems as $item){
            $item->delete();
        }
        $order->delete();
    }

    public function validateorderform(StoreOrderRequest $request){
    }
    public function validateEditOrderForm(UpdateOrderRequest $request){
    }

    public function export(BulkOrder $bulkorder, Request $request){
        if(! Auth::user()->can('export', Order::class)){
            abort(403, "Du hast keine Berechtigung, um Bestellungen zu exportieren");
        }

        if($bulkorder->id === null){
            abort(404, "Sammelbestellung fehlt");
        }

        $export = new OrdersExport();
        $export->bulkorder = $bulkorder;

        $products = 'Alle Artikel';

        if($request->has('products')){
            $export->products = $request->products;
            $products = [
                'all' => 'Alle Artikel',
                'supplier' => 'Lieferanten-Artikel',
                'own' => 'heinerenergie-Artikel'
            ][$request->products];
        }

        if($request->has('filename')){
            $filename = $request->filename;
        }else{
            $name = sprintf("Sammelbestellung %s", $bulkorder->name);
            $date = Carbon::now()->format('Y-m-d H:i:s');
            $filename = sprintf("%s %s %s.xlsx", $name, $date, $products);
        }
        return Excel::download($export, $filename);
    }

    public function checkPassword(RequireOrderPassword $request){
    }

    public function setChecked(Order $order){
        $this->auth($order, 'update');
        $order->checked = true;
        $order->save();
    }

    public function setUnchecked(Order $order){
        $this->auth($order, 'update');
        $order->checked = false;
        $order->save();
    }

    public function setAdvisors(Order $order, Request $request){
        $this->auth($order, 'addAdvisors');
        $order->shares()->sync($request->advisors);
    }

    public function sendMail(Order $order, Request $request){
        $this->auth($order, 'update');
        Mail::to($order->email)->send(new OrderChecking($order, $request->reason));
    }

    private function auth(Order $order, string $ability){
        if(! Auth::user()->can($ability, $order)){
            abort(403, "Du hast keine Berechtigung, diese Bestellung zu sehen");
        }
    }
}
