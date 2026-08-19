<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Carbon\Carbon;
use Database\Factories\ChecklistEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $updated_at
 */
class ChecklistEntry extends Model
{
    /** @use HasFactory<ChecklistEntryFactory> */
    use HasFactory;

    use HasUuid;

    protected $casts = [
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

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
