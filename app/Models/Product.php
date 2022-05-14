<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'sku', 'panelsCount', 'url', 'description']; 

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
}
