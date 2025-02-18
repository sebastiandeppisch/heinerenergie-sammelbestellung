<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Advice>
 */
class AdviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'street' => fake()->streetName(),
            'streetNumber' => fake()->buildingNumber(),
            'zip' => fake()->numberBetween(10000, 99999),
            'city' => fake()->city(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'commentary' => fake()->optional()->text(),
            'long' => fake()->optional()->longitude(),
            'lat' => fake()->optional()->latitude(),
        ];
    }
}
