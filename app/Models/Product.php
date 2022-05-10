<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
