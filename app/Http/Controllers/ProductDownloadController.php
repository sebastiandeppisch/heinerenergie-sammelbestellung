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
        return $product->downloads;
    }

    public function store(Product $product, StoreProductDownloadRequest $request)
    {
        $download = new ProductDownload($request->validated());
        $product->downloads()->save($download);
        return $download;
    }

    public function show(Product $product, ProductDownload $download)
    {
        return $download;
    }

    public function update(Product $product, UpdateProductDownloadRequest $request, ProductDownload $download)
    {
        $download->update($request->validated());
        return $download;
    }

    public function destroy(Product $product, ProductDownload $download)
    {
        $download->delete();
        return response()->noContent();
    }
}
