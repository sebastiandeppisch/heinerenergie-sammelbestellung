<?php

namespace App\Models;

use App\Contracts\Pointable;
use App\Models\Traits\HasUuid;
use App\Traits\HasPoints;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model implements Pointable
{
    use HasFactory;
    use HasPoints;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SubmissionField, $this>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class)->orderBy('sort_order');
    }

    public function handleCreators(): void
    {
        if ($this->formDefinition->adviceCreator) {
            $this->formDefinition->adviceCreator->createAdvice($this);
        }

        if ($this->formDefinition->mapPointCreator) {
            $this->formDefinition->mapPointCreator->createMapPoint($this);
        }
    }
}
