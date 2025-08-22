<?php

namespace Database\Factories;

use App\Models\FormSubmission;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
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
            'group_id' => Group::factory(),
        ];
    }
}
