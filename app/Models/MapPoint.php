<?php

namespace App\Models;

use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Contracts\Pointable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MapPoint extends Model
{
    /** @use HasFactory<\Database\Factories\MapPointFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'title',
        'description',
        'lng',
        'lat',
        'coordinate',
        'published'
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'published' => 'boolean'
    ];

    /**
     * @return MorphTo<Pointable>
     */
    public function pointable(): MorphTo{
        return $this->morphTo();
    }

}
