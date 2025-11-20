<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use App\Rules\AddressRule;
use App\Rules\CheckboxRequiredValidator;
use App\Rules\GeographicCoordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Override;

class FormField extends Model
{
    use HasFactory;
    use HasUuid;

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
        'form_definition_id' => 'integer',
    ];

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return HasMany<FormFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<SubmissionField, $this>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class);
    }

    public function getValidationRules(): array
    {
        $inRule = Rule::in($this->options->pluck('value')->toArray());

        $rules = match ($this->type) {
            FieldType::TEXT => ['string'],
            FieldType::TEXTAREA => ['string'],
            FieldType::NUMBER => ['numeric'],
            FieldType::EMAIL => ['email'],
            FieldType::PHONE => ['nullable', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
            // TODO fix array validation FieldType::SELECT => [$inRule],
            FieldType::RADIO => [$inRule],
            // TODO fix array validation FieldType::CHECKBOX => [$inRule],
            FieldType::FILE => [''], // TODO
            FieldType::DATE => ['date'],
            FieldType::GEO_COORDINATE => [new GeographicCoordinate],
            FieldType::ADDRESS => [new AddressRule],
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

        $requiredOptions = $this->options->filter(fn ($option) => $option->is_required)->pluck('label', 'value');

        if ($requiredOptions->isNotEmpty() && $this->type === FieldType::CHECKBOX) {
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
        $submissionField = $submission->submissionFields()->create([
            'form_field_id' => $this->id,
            'value' => $value,
            'sort_order' => $this->sort_order,
            'type' => $this->type,
            'label' => $this->label,
            'help_text' => $this->help_text,
            'required' => $this->required,
        ]);

        foreach ($this->options as $option) {
            $option->createSubmissionFieldOption($submissionField);
        }

        return $submissionField;
    }

    public function getSubmissionField(FormSubmission $submission): SubmissionField
    {
        return $this->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
    }

    #[Override]
    public function delete(): ?bool
    {
        SubmissionField::where('form_field_id', $this->id)->update(['form_field_id' => null]);
        FormDefinitionToAdvice::where('advice_type_field_id', $this->id)->update(['advice_type_field_id' => null]);

        FormFieldOption::where('form_field_id', $this->id)->get()->each->delete();

        return parent::delete();
    }
}
