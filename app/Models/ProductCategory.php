<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function bulkOrder(): BelongsTo
    {
        return $this->belongsTo(BulkOrder::class);
    }
}
