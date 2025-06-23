<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\FormDefinition;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_definition_id' => null,
            'form_name' => $this->faker->sentence(3),
            'form_description' => $this->faker->optional()->sentence(6),
            'submitted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
