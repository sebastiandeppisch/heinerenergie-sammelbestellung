<?php

namespace App\Models;

use App\Events\MapPointCreatedByFormSubmission;
use App\Models\Traits\HasUuid;
use Database\Factories\FormDefinitionToMapPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FormDefinitionToMapPoint extends Model
{
    /** @use HasFactory<FormDefinitionToMapPointFactory> */
    use HasFactory;

    use HasUuid;

    protected $table = 'form_definition_to_map_points';

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function titleField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'title_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function descriptionField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'description_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function coordinateField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'coordinate_field_id');
    }

    public function createMapPoint(FormSubmission $submission): MapPoint
    {
        $mapPoint = DB::transaction(function () use ($submission) {
            $titleField = $this->titleField->getSubmissionField($submission);
            $descriptionField = $this->descriptionField->getSubmissionField($submission);
            $coordinateField = $this->coordinateField->getSubmissionField($submission);

            // Extract lat/lng from coordinate field value
            $coordinateValue = $coordinateField->value;

            $mapPoint = MapPoint::create([
                'title' => $titleField->value,
                'description' => $descriptionField->value,
                'coordinate' => $coordinateValue,
                'published' => false, // unpublished initially
            ]);

            // Associate with the form submission as pointable
            $mapPoint->pointable()->associate($submission);
            $mapPoint->save();

            event(new MapPointCreatedByFormSubmission($mapPoint, $submission));

            return $mapPoint->fresh();
        });

        return $mapPoint;
    }
}
