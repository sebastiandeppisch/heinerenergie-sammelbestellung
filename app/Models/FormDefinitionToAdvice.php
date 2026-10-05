<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\AdviceTargetPayload;
use App\Enums\AdviceType;
use App\Models\Traits\HasUuid;
use Database\Factories\FormDefinitionToAdviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormDefinitionToAdvice extends Model
{
    /** @use HasFactory<FormDefinitionToAdviceFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'advice_type_home_option_value',
        'advice_type_virtual_option_value',
        'advice_type_direct',
    ];

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
    public function addressField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'address_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function emailField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'email_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function phoneField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'phone_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function firstNameField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'first_name_field_id');

    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function adviceTypeField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'advice_type_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function lastNameField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'last_name_field_id');
    }

    /**
     * Optional checkbox that must be ticked for an advice to be created.
     *
     * @return BelongsTo<FormField, $this>
     */
    public function conditionField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'condition_field_id');
    }

    /**
     * Reads the advice from the submission. Null when the condition checkbox was not ticked.
     */
    public function prepare(FormSubmission $submission): ?AdviceTargetPayload
    {
        if (! $this->isConditionMet($submission)) {
            return null;
        }

        return new AdviceTargetPayload(
            first_name: $this->firstNameField->getSubmissionField($submission)->value,
            last_name: $this->lastNameField->getSubmissionField($submission)->value,
            email: $this->emailField->getSubmissionField($submission)->value,
            phone: $this->phoneField->getSubmissionField($submission)->value,
            address: $this->addressField->getSubmissionField($submission)->value,
            type: $this->getAdviceType($submission),
            group_id: $submission->group_id,
        );
    }

    /**
     * A submission without an answer to the condition counts as not ticked.
     */
    private function isConditionMet(FormSubmission $submission): bool
    {
        if ($this->conditionField === null) {
            return true;
        }

        return ! empty($this->conditionField->submissionFields()->where('form_submission_id', $submission->id)->first()?->value);
    }

    /**
     * Get the advice type from either direct value or mapped field value
     */
    private function getAdviceType(FormSubmission $submission): AdviceType
    {
        // If direct advice type is set, use it
        if ($this->advice_type_direct !== null) {
            return AdviceType::from((int) $this->advice_type_direct);
        }

        // Otherwise, map from field value
        if ($this->adviceTypeField === null) {
            // Fallback to Virtual if no field is set
            return AdviceType::Virtual;
        }

        $adviceTypeField = $this->adviceTypeField->getSubmissionField($submission);
        /** @var mixed $submittedValue */
        $submittedValue = $adviceTypeField->value;

        return $this->mapAdviceType($submittedValue);
    }

    /**
     * Map a form option value to the correct AdviceType enum
     */
    private function mapAdviceType(mixed $optionValue): AdviceType
    {
        // Handle array value (from checkboxes/multi-select) by taking the first value
        if (is_array($optionValue)) {
            $optionValue = $optionValue[0] ?? null;
        }

        if ($optionValue === $this->advice_type_home_option_value) {
            return AdviceType::Home;
        }

        if ($optionValue === $this->advice_type_virtual_option_value) {
            return AdviceType::Virtual;
        }

        // Fallback to Virtual if no match found
        return AdviceType::Virtual;
    }
}
