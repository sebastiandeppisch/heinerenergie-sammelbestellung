<?php

namespace App\Models;

use App\Enums\AdviceType;
use App\Events\Advice\AdviceCreatedByFormSubmission;
use App\Jobs\SendNewAdviceInfoToAdvisors;
use App\Mail\AdviceCreated;
use App\Models\Traits\HasUuid;
use Database\Factories\FormDefinitionToAdviceFactory;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Mail;

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

    public function createAdvice(FormSubmission $submission): Advice
    {
        $advice = DB::transaction(function () use ($submission) {
            $addressField = $this->addressField->getSubmissionField($submission);
            $emailField = $this->emailField->getSubmissionField($submission);
            $phoneField = $this->phoneField->getSubmissionField($submission);
            $firstNameField = $this->firstNameField->getSubmissionField($submission);
            $lastNameField = $this->lastNameField->getSubmissionField($submission);

            // Determine advice type: use direct value if set, otherwise map from field
            $adviceType = $this->getAdviceType($submission);

            $advice = Advice::create([
                'address' => $addressField->value,
                'email' => $emailField->value,
                'phone' => $phoneField->value,
                'first_name' => $firstNameField->value,
                'last_name' => $lastNameField->value,
                'group_id' => $submission->group_id,
                'type' => $adviceType,
            ]);

            $advice->save();

            event(new AdviceCreatedByFormSubmission($advice, $submission));

            $advice = $advice->fresh();

            return $advice;
        });

        Log::info('Created advice from form submission', [
            'advice_id' => $advice->id,
            'form_submission_id' => $submission->id,
        ]);
        SendNewAdviceInfoToAdvisors::dispatch($advice);

        Mail::to($advice->email)->send(new AdviceCreated($advice));

        return $advice;
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
