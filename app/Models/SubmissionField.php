<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SubmissionField extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'value',
        'sort_order',
        'field_label',
        'field_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'field_type' => FieldType::class,
        'value' => 'json',
    ];

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

}
