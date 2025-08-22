<?php

namespace App\Models;

use App\Contracts\Pointable;
use App\Models\Traits\HasUuid;
use App\ValueObjects\Coordinate;
use Database\Factories\MapPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Coordinate $coordinate
 */
class MapPoint extends Model
{
    /** @use HasFactory<MapPointFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'name',
        'title',
        'description',
        'lng',
        'lat',
        'coordinate',
        'published',
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'published' => 'boolean',
    ];

    /**
     * @return MorphTo<Model&Pointable, $this>
     */
    public function pointable(): MorphTo
    {
        return $this->morphTo();
    }
}
