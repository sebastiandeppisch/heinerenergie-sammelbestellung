<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The value of the form field (source) is stored in the category field (target) of the created point.
 */
class FormDefinitionToMapPointField extends Model
{
    protected $fillable = [
        'form_definition_to_map_point_id',
        'target_field_id',
        'source_field_id',
    ];

    /**
     * @return BelongsTo<FormDefinitionToMapPoint, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(FormDefinitionToMapPoint::class, 'form_definition_to_map_point_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function targetField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'target_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function sourceField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'source_field_id');
    }
}
