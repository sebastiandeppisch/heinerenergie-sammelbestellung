<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['quantity', 'product_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function normalize(Order $order): void
    {
        $duplicates = $order->orderItems->pluck('product_id')->duplicatesStrict();
        DB::beginTransaction();

        foreach ($duplicates as $productId) {
            $duplicateItems = OrderItem::where('order_id', $order->id)->where('product_id', $productId);
            $quantity = $duplicateItems->pluck("quantity")->sum();

            $duplicateItems->delete();

            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->product_id = $productId;
            $item->quantity = $quantity;
            $item->save();
        }

        OrderItem::where('quantity', 0)->delete();

        DB::commit();
    }
}
