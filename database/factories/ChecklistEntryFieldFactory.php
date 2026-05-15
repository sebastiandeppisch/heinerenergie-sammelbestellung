<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\ChecklistEntry;
use App\Models\ChecklistEntryField;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistEntryField>
 */
class ChecklistEntryFieldFactory extends Factory
{
    protected $model = ChecklistEntryField::class;

    public function definition(): array
    {
        return [
            'checklist_entry_id' => ChecklistEntry::factory(),
            'form_field_id' => FormField::factory(),
            'value' => null,
            'type' => FieldType::TEXT->value,
            'label' => ucfirst($this->faker->unique()->word()),
            'help_text' => null,
            'required' => false,
            'sort_order' => 0,
        ];
    }
}
