<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path'
    ];

    public function mapPoints()
    {
        return $this->hasMany(MapPoint::class);
    }
}
