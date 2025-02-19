<?php

namespace App\Models;

use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use NumberFormatter;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'street', 'streetNumber', 'zip', 'city', 'email', 'phone', 'commentary', 'advisor_id'];

    protected $appends = ['price', 'panelsCount', 'archived', 'shares_ids'];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getPriceAttribute(): float
    {
        return $this->orderItems->reduce(fn (float $sum, OrderItem $item) => $sum + $item->quantity * $item->product->price, 0);
    }

    public function getFormattedPriceAttribute(): string
    {
        return (new NumberFormatter('de_DE', NumberFormatter::CURRENCY))->formatCurrency($this->price, 'EUR');
    }

    public function getPanelsCountAttribute(): int
    {
        return $this->orderItems->reduce(fn (int $sum, OrderItem $item) => $sum + $item->product->panelsCount * $item->quantity, 0);
    }

    public function getStreetWithNumberAttribute(): string
    {
        return sprintf('%s %s', $this->street, $this->streetNumber);
    }

    public function normalize(): void
    {
        OrderItem::normalize($this);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute()
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function bulkOrder(): BelongsTo
    {
        return $this->belongsTo(BulkOrder::class);
    }

    public function getArchivedAttribute(): bool
    {
        return $this->bulkOrder->archived;
    }

    #[\Override]
    public function save(array $options = [])
    {
        if ($this->archived) {
            throw new InvalidArgument('An archived order can not be changed');
        }
        parent::save($options);
    }

    public function shares(): MorphToMany
    {
        return $this->morphToMany(User::class, 'sharing', 'sharings', 'sharing_id', 'advisor_id');
    }

    public function getSharesIdsAttribute(): array
    {
        return $this->shares->pluck('id')->toArray();
    }

    protected function casts(): array
    {
        return [
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
    }
}
