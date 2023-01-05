<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDownload extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
