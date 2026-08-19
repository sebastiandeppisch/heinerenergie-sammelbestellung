<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\ChecklistEntryFieldOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class ChecklistEntryFieldOption extends Model
{
    /** @use HasFactory<ChecklistEntryFieldOptionFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'checklist_entry_field_id',
        'form_field_option_id',
        'label',
        'value',
        'sort_order',
        'is_default',
        'is_required',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ChecklistEntryField, $this>
     */
    public function checklistEntryField(): BelongsTo
    {
        return $this->belongsTo(ChecklistEntryField::class);
    }

    /**
     * @return BelongsTo<FormFieldOption, $this>
     */
    public function formFieldOption(): BelongsTo
    {
        return $this->belongsTo(FormFieldOption::class);
    }
}
