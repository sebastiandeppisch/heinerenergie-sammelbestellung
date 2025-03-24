<?php

namespace Database\Factories;

use App\Models\Group;
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

        if (Group::count() > 0) {
            $group = Group::inRandomOrder()->first();
        } else {
            $group = Group::factory();
        }

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
            'lng' => fake()->optional()->longitude(),
            'lat' => fake()->optional()->latitude(),
            'group_id' => $group,
        ];
    }
}
