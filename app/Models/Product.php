<?php

namespace App\Models;

use App\Models\Order;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'sku', 'panelsCount', 'url', 'description', 'product_category_id']; 

    protected $casts = [
        'name' => 'string',
        'price' => 'float',
        'sku' => 'string',
        'panelsCount' => 'integer',
        'url' => 'string',
        'description' => 'string'
    ]; 

    public function orderItems(): HasMany{
        return $this->hasMany(OrderItem::class);
    }

    public function order(): BelongsTo{
        return $this->belongsTo(Order::class)->using(OrderItem::class);
    }

    public function productCategory(): BelongsTo{
        return $this->belongsTo(ProductCategory::class);
    } 

    public function productDownloads(): HasMany{
        return $this->hasMany(ProductDownload::class);
    }
}
