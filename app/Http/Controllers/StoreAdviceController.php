<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\BulkOrder;
use App\Models\OrderItem;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdviceRequest;

class StoreAdviceController extends Controller
{
    public function __invoke(StoreAdviceRequest $request)
    {
        var_dump($request->validated());
    }
}
