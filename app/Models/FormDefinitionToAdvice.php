<?php

namespace App\Models;

use Database\Factories\FormDefinitionToAdviceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormDefinitionToAdvice extends Model
{
    /** @use HasFactory<FormDefinitionToAdviceFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<FormDefinition>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return BelongsTo<FormField>
     */
    public function addressField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'address_field_id');
    }

    /**
     * @return BelongsTo<FormField>
     */
    public function emailField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'email_field_id');
    }

    /**
     * @return BelongsTo<FormField>
     */
    public function phoneField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'phone_field_id');
    }

    /**
     * @return BelongsTo<FormField>
     */
    public function firstNameField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'first_name_field_id');

    }

    /**
     * @return BelongsTo<FormField>
     */
    public function lastNameField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'last_name_field_id');
    }

    public function createAdvice(FormSubmission $submission): Advice
    {
        $addressField = $this->addressField->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
        $emailField = $this->emailField->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
        $phoneField = $this->phoneField->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
        $firstNameField = $this->firstNameField->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();
        $lastNameField = $this->lastNameField->submissionFields()->where('form_submission_id', $submission->id)->firstOrFail();

        $advice = Advice::create([
            'address' => $addressField->value,
            'email' => $emailField->value,
            'phone' => $phoneField->value,
            'firstName' => $firstNameField->value,
            'lastName' => $lastNameField->value,
            'group_id' => $submission->group_id,
        ]);

        // TODO add Advice Submission Event

        return $advice;
    }
}
