<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFieldOption extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'submission_field_id',
        'form_field_option_id',
        'option_label_snapshot',
        'value',
        'label',
        'sort_order',
        'is_default',
    ];

    /**
     * @return BelongsTo<SubmissionField, $this>
     */
    public function submissionField(): BelongsTo
    {
        return $this->belongsTo(SubmissionField::class);
    }

    /**
     * @return BelongsTo<FormFieldOption, $this>
     */
    public function formFieldOption(): BelongsTo
    {
        return $this->belongsTo(FormFieldOption::class);
    }
}
