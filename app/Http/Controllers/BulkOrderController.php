<?php

namespace App\Http\Controllers;

use App\Models\BulkOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreBulkOrderRequest;
use App\Http\Requests\UpdateBulkOrderRequest;

class BulkOrderController extends Controller
{
    public function index()
    {
        return BulkOrder::all();
    }

    public function store(StoreBulkOrderRequest $request)
    {
        DB::transaction(function () use ($request) {
            $bulkOrder = BulkOrder::create($request->validated());
            if($request->has('copy_from')){
                $oldBulkOrder = BulkOrder::findOrFail($request->input('copy_from'));
                $bulkOrder->copyFrom($oldBulkOrder);
            }
            return $bulkOrder;
        });
    }

    public function show(BulkOrder $bulkOrder)
    {
        return $bulkOrder;
    }

    public function update(UpdateBulkOrderRequest $request, BulkOrder $bulkorder)
    {
        $bulkorder->fill($request->validated());
        $bulkorder->save();
        return $bulkorder;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BulkOrder  $bulkOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(BulkOrder $bulkOrder)
    {
        $bulkOrder->delete();
        return response()->noContent();
    }
}
