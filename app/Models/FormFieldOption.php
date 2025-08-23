<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFieldOption extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'form_field_id',
        'label',
        'value',
        'sort_order',
        'is_default',
        'is_required',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_required' => 'boolean',
    ];

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function createSubmissionFieldOption(SubmissionField $submissionField): SubmissionFieldOption
    {
        return $submissionField->options()->create([
            'form_field_option_id' => $this->id,
            'value' => $this->value,
            'label' => $this->label,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
        ]);
    }
}
