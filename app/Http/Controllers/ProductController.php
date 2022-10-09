<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\RequireOrderPassword;
use App\Http\Requests\UpdateProductRequest;
use App\Models\BulkOrder;

class ProductController extends Controller
{

    private function dxFilter(Request $request, Builder $builder): Builder{
        if(isset($request->searchOperation) && isset($request->searchValue) && isset($request->searchExpr)){
			if($request->searchOperation === "contains"){
				$builder = $builder->where($request->searchExpr, 'like', "%".$request->searchValue."%");
			}
		}
        return $builder;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Bulkorder $bulkorder, RequireOrderPassword $request)
    {
        if($bulkorder->id === null){
            $bulkorder = BulkOrder::getCurrentBulkOrder();
        }
        $query = Product::where('bulk_order_id', $bulkorder->id);
        return $this->dxFilter($request, $query)->with('downloads')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BulkOrder $bulkorder, StoreProductRequest $request)
    {
        $product = new Product($request->validated());
        $product->bulk_order_id = $bulkorder->id;
        $product->save();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(BulkOrder $bulkorder, Product $product, UpdateProductRequest $request)
    {
        $product->fill($request->validated());
        $product->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
    }

    public function show(Product $product){
        return $product->with('downloads');
    }
}
