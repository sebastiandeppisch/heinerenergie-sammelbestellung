<?php

namespace App\Models;

use App\Contracts\Pointable;
use App\Models\Traits\HasUuid;
use App\Traits\HasPoints;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $submitted_at
 */
class FormSubmission extends Model implements Pointable
{
    use HasFactory;
    use HasPoints;
    use HasUuid;

    protected $fillable = [
        'form_definition_id',
        'advice_id',
        'form_name',
        'form_description',
        'submitted_at',
        'group_id',
    ];

    public function casts()
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return HasMany<SubmissionField, $this>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class)->orderBy('sort_order');
    }

    public function handleCreators(): void
    {
        if ($this->formDefinition->adviceCreator) {
            $advice = $this->formDefinition->adviceCreator->createAdvice($this);
            $this->update([
                'advice_id' => $advice->id,
            ]);
        }

        if ($this->formDefinition->mapPointCreator) {
            $this->formDefinition->mapPointCreator->createMapPoint($this);
        }
    }
}
