<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDownload;
use App\Http\Requests\StoreProductDownloadRequest;
use App\Http\Requests\UpdateProductDownloadRequest;

class ProductDownloadController extends Controller
{
    public function index(Product $product)
    {
        return $product->productDownloads;
    }

    public function store(Product $product, StoreProductDownloadRequest $request)
    {
        $productDownload = new ProductDownload($request->validated());
        $productDownload->product_id = $product->id;
        $productDownload->save();
        return $productDownload;
    }

    public function show(Product $product, ProductDownload $productdownload)
    {
        return $productdownload;
    }

    public function update(Product $product, UpdateProductDownloadRequest $request, ProductDownload $productdownload)
    {
        $productdownload->fill($request->validated());
        $productdownload->save();
        return $productdownload;
    }

    public function destroy(Product $product, ProductDownload $productDownload)
    {
        $productDownload->delete();
        return response()->noContent();
    }
}
