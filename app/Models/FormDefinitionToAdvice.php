<?php

namespace App\Models;

use App\Events\Advice\AdviceCreatedByFormSubmission;
use App\Mail\AdviceCreated;
use Database\Factories\FormDefinitionToAdviceFactory;
use DB;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mail;

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
        $advice = DB::transaction(function ()  use ($submission){
            $addressField = $this->addressField->getSubmissionField($submission);
            $emailField = $this->emailField->getSubmissionField($submission);
            $phoneField = $this->phoneField->getSubmissionField($submission);
            $firstNameField = $this->firstNameField->getSubmissionField($submission);
            $lastNameField = $this->lastNameField->getSubmissionField($submission);


            $advice = Advice::create([
                'address' => $addressField->value,
                'email' => $emailField->value,
                'phone' => $phoneField->value,
                'firstName' => $firstNameField->value,
                'lastName' => $lastNameField->value,
                'group_id' => $submission->group_id,
            ]);

            $advice->save();

            event(new AdviceCreatedByFormSubmission($advice, $submission));


            $advice = $advice->fresh();
            return $advice;
        });

        Mail::to($advice->email)->send(new AdviceCreated($advice));
        return $advice;
    }
}
