<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Http\Requests\RequireOrderPassword;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;

class ProductCategoryController extends Controller
{

    public function index(RequireOrderPassword $request)
    {
        return ProductCategory::all();
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
