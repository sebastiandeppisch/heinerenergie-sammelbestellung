<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class MapPointFieldOption extends Model
{
    use HasUuid;

    protected $fillable = [
        'map_point_field_id',
        'label',
        'value',
        'sort_order',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MapPointField, $this>
     */
    public function mapPointField(): BelongsTo
    {
        return $this->belongsTo(MapPointField::class);
    }
}
