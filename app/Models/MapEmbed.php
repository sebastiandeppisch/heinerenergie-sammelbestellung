<?php

namespace App\Models;

use App\Casts\Coordinate;
use App\Models\Traits\HasUuid;
use Database\Factories\MapEmbedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property \App\ValueObjects\Coordinate $coordinate
 */
class MapEmbed extends Model
{
    /** @use HasFactory<MapEmbedFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'coordinate',
        'zoom',
        'show_table',
    ];

    protected $casts = [
        'coordinate' => Coordinate::class,
        'show_table' => 'boolean',
    ];

    /**
     * @return BelongsToMany<MapPointCategory, $this>
     */
    public function mapPointCategories(): BelongsToMany
    {
        return $this->belongsToMany(MapPointCategory::class);
    }
}
