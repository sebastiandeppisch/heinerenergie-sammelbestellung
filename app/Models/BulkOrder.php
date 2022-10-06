<?php

namespace App\Models;

use Exception;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BulkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'archived'
    ];

    protected $casts = [
        'name' => 'string',
        'archived' => 'boolean'
    ];

    public static function getCurrentBulkOrder(): ?BulkOrder{
        if(BulkOrder::where('archived', false)->count() > 1){
            throw new Exception('There are more than 1 current bulk orders');
        }
        return BulkOrder::where('archived', false)->first();    
    }

    public function orders(): HasMany{
        return $this->hasMany(Order::class);
    }

    public function products(): HasMany{
        return $this->hasMany(Product::class);
    }

    public function productCategories(): HasMany{
        return $this->hasMany(ProductCategory::class);
    }

    public function copyFrom(BulkOrder $old): void{
        foreach($old->productCategories as $category){
            $newCategory = new ProductCategory();
            $newCategory->name = $category->name;
            $newCategory->bulk_order_id = $this->id;
            $newCategory->save();

            foreach($category->products as $product){
                $product->copy($this, $category->id);
            }
        }
        foreach($this->products()->whereNull('product_category_id')->get() as $product){
            $product->copy($this);
        }
    }

}
