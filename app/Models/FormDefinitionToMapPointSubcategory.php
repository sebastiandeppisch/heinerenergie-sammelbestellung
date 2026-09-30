<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Points of a form submission with this option in the sub category field are created in this category.
 */
class FormDefinitionToMapPointSubcategory extends Model
{
    protected $fillable = [
        'form_definition_to_map_point_id',
        'option_value',
        'map_point_category_id',
    ];

    /**
     * @return BelongsTo<FormDefinitionToMapPoint, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(FormDefinitionToMapPoint::class, 'form_definition_to_map_point_id');
    }

    /**
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'map_point_category_id');
    }
}
