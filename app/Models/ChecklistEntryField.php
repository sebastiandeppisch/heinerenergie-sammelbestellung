<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistEntryField extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'checklist_entry_id',
        'form_field_id',
        'value',
        'type',
        'sort_order',
        'label',
        'help_text',
        'required',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'required' => 'boolean',
        'type' => FieldType::class,
        'value' => 'json',
    ];

    /**
     * @return BelongsTo<ChecklistEntry, $this>
     */
    public function checklistEntry(): BelongsTo
    {
        return $this->belongsTo(ChecklistEntry::class);
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    /**
     * @return HasMany<ChecklistEntryFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ChecklistEntryFieldOption::class)->orderBy('sort_order');
    }
}
