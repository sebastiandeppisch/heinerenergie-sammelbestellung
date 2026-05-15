<?php

namespace Database\Factories;

use App\Models\ChecklistEntryField;
use App\Models\ChecklistEntryFieldOption;
use App\Models\FormFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistEntryFieldOption>
 */
class ChecklistEntryFieldOptionFactory extends Factory
{
    protected $model = ChecklistEntryFieldOption::class;

    public function definition(): array
    {
        return [
            'checklist_entry_field_id' => ChecklistEntryField::factory(),
            'form_field_option_id' => FormFieldOption::factory(),
            'label' => ucfirst($this->faker->unique()->word()),
            'value' => $this->faker->slug(2),
            'sort_order' => 0,
            'is_default' => false,
            'is_required' => false,
        ];
    }
}
