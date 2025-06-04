<?php

namespace Database\Factories;

use App\Models\FormDefinition;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormDefinitionFactory extends Factory
{
    protected $model = FormDefinition::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true) . ' Form',
            'description' => $this->faker->optional(0.8)->sentence(),
            'is_active' => $this->faker->boolean(80),
        ];
    }

    public function withFields(int $count = 3)
    {
        return $this->afterCreating(function (FormDefinition $formDefinition) use ($count) {
            $formDefinition->fields()->saveMany(
                FormField::factory()->count($count)->make()
            );
        });
    }
}
