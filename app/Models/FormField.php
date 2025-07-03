<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Rules\CheckboxRequiredValidator;
use App\Rules\GeographicCoordinate;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class FormField extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'form_definition_id',
        'type',
        'label',
        'placeholder',
        'help_text',
        'required',
        'default_value',
        'sort_order',
        'min_length',
        'max_length',
        'min_value',
        'max_value',
        'accepted_file_types',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => FieldType::class,
        'required' => 'boolean',
        'min_length' => 'integer',
        'max_length' => 'integer',
        'min_value' => 'float',
        'max_value' => 'float',
        'sort_order' => 'integer',
        'accepted_file_types' => 'array',
    ];

    /**
     * @return BelongsTo<FormDefinition>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return HasMany<FormFieldOption>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<SubmissionField>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class);
    }

    public function getValidationRules(): array
    {
        $inRule = Rule::in($this->options->pluck('id')->toArray());

        $rules = match ($this->type) {
            FieldType::TEXT => ['string'],
            FieldType::TEXTAREA => ['string'],
            FieldType::NUMBER => ['numeric'],
            FieldType::EMAIL => ['email'],
            FieldType::PHONE => ['string', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
            // TODO fix array validation FieldType::SELECT => [$inRule],
            FieldType::RADIO => [$inRule],
            // TODO fix array validation FieldType::CHECKBOX => [$inRule],
            FieldType::FILE => [''], // TODO
            FieldType::DATE => ['date'],
            FieldType::GEO_COORDINATE => [new GeographicCoordinate],
            default => [],
        };

        if ($this->type->supportsLengthValidation()) {
            if (isset($this->min_length)) {
                $rules[] = 'min:'.$this->min_length;
            }
            if (isset($this->max_length)) {
                $rules[] = 'max:'.$this->max_length;
            }
        }

        if ($this->type->supportsNumericValidation()) {
            if (isset($this->min_value)) {
                $rules[] = 'min:'.$this->min_value;
            }
            if (isset($this->max_value)) {
                $rules[] = 'max:'.$this->max_value;
            }
        }

        if ($this->type->requiresGeoCoordinate()) {
            // TODO
        }


        $requiredOptions = $this->options->filter(fn($option) => $option->is_required)->pluck('label', 'id');


        if($requiredOptions->isNotEmpty()) {
            $rules[] = new CheckboxRequiredValidator(
                requiredOptions: $requiredOptions->toArray()
            );
        }

        if ($this->required) {
            $rules[] = 'required';
        }

        return $rules;
    }

    public function createSubmissionField(FormSubmission $submission, mixed $value): SubmissionField
    {
        return $submission->submissionFields()->create([
            'form_field_id' => $this->id,
            'field_label' => $this->label,
            'sort_order' => $this->sort_order,
            'field_type' => $this->type,
            'value' => $value,
        ]);
    }

    public function getSubmissionField(FormSubmission $submission): SubmissionField
    {
        return $this->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
    }
}
