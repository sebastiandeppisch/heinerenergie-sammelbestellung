<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function asCoordinate(): Coordinate
    {
        if ($this->field_type != FieldType::GEO_COORDINATE) {
            throw new RuntimeException('This method can only be called for an geo coordinate field');
        }

        return Coordinate::fromArray($this->value);
    }
}
