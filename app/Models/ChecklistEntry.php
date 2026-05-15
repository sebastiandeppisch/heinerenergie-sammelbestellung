<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistEntry extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'form_definition_id',
        'advice_id',
    ];

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return BelongsTo<Advice, $this>
     */
    public function advice(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    /**
     * @return HasMany<ChecklistEntryField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ChecklistEntryField::class)->orderBy('sort_order');
    }
}
