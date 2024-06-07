<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequireOrderPassword;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\BulkOrder;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(BulkOrder $bulkorder, RequireOrderPassword $request)
    {
        if ($bulkorder->id === null) {
            $bulkorder = BulkOrder::getCurrentBulkOrder();
        }

        return ProductCategory::where('bulk_order_id', $bulkorder->id)->get();
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $productCategory = new ProductCategory();
        $productCategory->fill($request->all());
        $productCategory->save();

        return $productCategory;
    }

    public function show(ProductCategory $productCategory)
    {
        return $productCategory;
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory)
    {
        $productCategory->fill($request->all());
        $productCategory->save();

        return $productCategory;
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();
    }
}
