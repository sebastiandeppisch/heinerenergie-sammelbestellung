<?php

namespace App\Models;

use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'street', 'streetNumber', 'zip', 'city', 'email', 'phone', 'commentary', 'advisor_id'];

    protected $appends = ['price', 'panelsCount'];

    protected $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'street' => 'string',
        'streetNumber' => 'string',
        'zip' => 'integer',
        'city' => 'string',
        'email' => 'string',
        'phone' => 'string'
    ];

    public function orderItems(): HasMany{
        return $this->hasMany(OrderItem::class);
    }

    public function products(): HasMany{
        return $this->hasManys(Product::class)->using(OrderItem::class);
    }

    public function getPriceAttribute(): float{
        return $this->orderItems->reduce(fn(float $sum, OrderItem $item) => 
            $sum + $item->quantity*$item->product->price
        , 0);
    }

    public function getPanelsCountAttribute(): int{
        return $this->orderItems->reduce(fn(int $sum, OrderItem $item) => 
            $sum + $item->product->panelsCount * $item->quantity
        , 0);
    }

    public function getStreetWithNumberAttribute(): string{
        return sprintf("%s %s", $this->street, $this->streetNumber);
    }

    public function normalize(): void{
        OrderItem::normalize($this);
    }

    public function advisor(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute(){
        return sprintf("%s %s", $this->firstName, $this->lastName);
    }
}