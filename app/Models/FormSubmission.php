<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Models\Traits\HasUuid;
use App\Traits\HasPoints;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $submitted_at
 *
 * @implements Pointable<self>
 */
class FormSubmission extends Model implements Pointable
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    /** @use HasPoints<self> */
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

    /**
     * @return array<string, string>
     */
    public function casts()
    {
        return [
            'submitted_at' => 'datetime',
            'seen' => 'boolean',
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
