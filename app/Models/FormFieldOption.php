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

    /**
     * @var array<string>
     */
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

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
