<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Str;

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

    protected $attributes = [
        'is_default' => false,
        'is_required' => false,
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
            'label' => $this->label,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
            'value' => $this->value,
        ]);
    }

    #[Override]
    public function delete(): ?bool
    {
        SubmissionFieldOption::where('form_field_option_id', $this->id)->update(['form_field_option_id' => null]);

        return parent::delete();
    }

    #[Override]
    public function save(array $options = []): bool
    {
        if ($this->sort_order === null) {
            $this->sort_order = FormFieldOption::where('form_field_id', $this->form_field_id)->max('sort_order') + 1;
        }

        if ($this->value === null) {
            $this->value = Str::uuid();
        }

        return parent::save($options);
    }
}
