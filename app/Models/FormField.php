<?php

namespace App\Models;

use App\Casts\FieldConfigurationCast;
use App\Enums\FieldType;
use App\ValueObjects\FieldConfiguration;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'form_definition_id',
        'type',
        'name',
        'label',
        'placeholder',
        'help_text',
        'required',
        'default_value',
        'sort_order',
        'min_length',
        'max_length',
        'min_value',
        'max_value',
        'accepted_file_types',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => FieldType::class,
        'required' => 'boolean',
        'min_length' => 'integer',
        'max_length' => 'integer',
        'min_value' => 'float',
        'max_value' => 'float',
        'sort_order' => 'integer',
        'accepted_file_types' => 'array',
    ];


    /**
     * Get the options for this field (for select, radio, etc.).
     */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }
}
