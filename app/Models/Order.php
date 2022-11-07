<?php

namespace App\Models;

use App\Models\Product;
use App\Models\BulkOrder;
use App\Models\OrderItem;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'street', 'streetNumber', 'zip', 'city', 'email', 'phone', 'commentary', 'advisor_id'];

    protected $appends = ['price', 'panelsCount', 'archived'];

    protected $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'street' => 'string',
        'streetNumber' => 'string',
        'zip' => 'integer',
        'city' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'commentary' => 'string',
        'advisor_id' => 'integer',
        'checked' => 'boolean',
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

    public function bulkOrder(): BelongsTo{
        return $this->belongsTo(BulkOrder::class);
    }

    public function getArchivedAttribute(): bool{
        return $this->bulkOrder->archived;
    }

    public function save(array $options = [])
    {
        if($this->archived){
            throw new InvalidArgument("An archived order can not be changed");
        }
        parent::save($options);
    }
}