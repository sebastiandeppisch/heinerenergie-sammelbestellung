<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFieldOption extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $fillable = [
        'submission_field_id',
        'form_field_option_id',
        'option_label_snapshot',
        'option_value_snapshot',
    ];

    /**
     * @return BelongsTo<SubmissionField>
     */
    public function submissionField(): BelongsTo
    {
        return $this->belongsTo(SubmissionField::class);
    }

    /**
     * @return BelongsTo<FormFieldOption>
     *
     */
    public function formFieldOption(): BelongsTo
    {
        return $this->belongsTo(FormFieldOption::class);
    }

    /**
     * Create a snapshot of the given form field option.
     */
    public static function createFromFormFieldOption(SubmissionField $submissionField, FormFieldOption $formFieldOption): self
    {
        return self::create([
            'submission_field_id' => $submissionField->id,
            'form_field_option_id' => $formFieldOption->id,
            'option_label_snapshot' => $formFieldOption->label,
            'option_value_snapshot' => $formFieldOption->value,
        ]);
    }
}
