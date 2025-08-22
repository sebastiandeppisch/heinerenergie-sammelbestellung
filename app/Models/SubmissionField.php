<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class SubmissionField extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * @var array<string, mixed>
     */
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'value',
        'type',
        'sort_order',
        'label',
        'help_text',
        'required',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'type' => FieldType::class,
        'value' => 'json',
    ];

    /**
     * @return BelongsTo<FormSubmission, $this>
     */
    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    public function asCoordinate(): Coordinate
    {
        if ($this->type != FieldType::GEO_COORDINATE) {
            throw new RuntimeException('This method can only be called for an geo coordinate field');
        }

        return Coordinate::fromArray($this->value);
    }

    /**
     * @return HasMany<SubmissionFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(SubmissionFieldOption::class);
    }
}
