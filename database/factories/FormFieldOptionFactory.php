<?php

namespace Database\Factories;

use App\Models\FormField;
use App\Models\FormFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormFieldOptionFactory extends Factory
{
    protected $model = FormFieldOption::class;

    public function definition()
    {
        static $optionCount = 0;
        $optionCount++;

        return [
            'form_field_id' => FormField::factory(),
            'label' => 'Option '.$optionCount,
            'value' => 'option_'.$optionCount,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_default' => $this->faker->boolean(20),
        ];
    }
}
