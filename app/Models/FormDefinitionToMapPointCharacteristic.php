<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Points of a form submission with this option checked in the characteristics field get this characteristic.
 */
class FormDefinitionToMapPointCharacteristic extends Model
{
    protected $fillable = [
        'form_definition_to_map_point_id',
        'option_value',
        'map_point_characteristic_id',
    ];

    /**
     * @return BelongsTo<FormDefinitionToMapPoint, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(FormDefinitionToMapPoint::class, 'form_definition_to_map_point_id');
    }

    /**
     * @return BelongsTo<MapPointCharacteristic, $this>
     */
    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(MapPointCharacteristic::class, 'map_point_characteristic_id');
    }
}
