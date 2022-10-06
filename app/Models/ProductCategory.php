<?php

namespace App\Models;

use App\Models\Product;
use App\Models\BulkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    
    public function products(): HasMany{
        return $this->hasMany(Product::class);
    }

    public function bulkOrders(): BelongsTo{
        return $this->belongsTo(BulkOrder::class);
    }
}
