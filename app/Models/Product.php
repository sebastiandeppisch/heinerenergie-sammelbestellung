<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'sku', 'panelsCount', 'description', 'product_category_id', 'is_supplier_product'];

    protected $casts = [
        'name' => 'string',
        'price' => 'float',
        'sku' => 'string',
        'panelsCount' => 'integer',
        'description' => 'string',
        'is_supplier_product' => 'boolean',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class);
    }

    public function bulkOrder(): BelongsTo
    {
        return $this->belongsTo(BulkOrder::class);
    }

    public function copy(BulkOrder $bulkOrder, ?ProductCategory $productCategory = null): self
    {
        $newProduct = new Product();
        $newProduct->name = $this->name;
        $newProduct->price = $this->price;
        $newProduct->sku = $this->sku;
        $newProduct->panelsCount = $this->panelsCount;
        $newProduct->description = $this->description;
        $newProduct->product_category_id = $productCategory?->id;
        $newProduct->bulk_order_id = $bulkOrder->id;
        $newProduct->save();

        foreach ($this->downloads as $download) {
            $newDownload = new ProductDownload();
            $newDownload->name = $download->name;
            $newDownload->url = $download->url;
            $newDownload->product_id = $newProduct->id;
            $newDownload->save();
        }

        return $newProduct;
    }

    public function delete(): bool
    {
        $this->downloads()->delete();

        return parent::delete();
    }
}
