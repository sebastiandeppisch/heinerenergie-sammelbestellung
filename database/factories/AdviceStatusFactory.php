<?php

namespace Database\Factories;

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatus;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdviceStatus>
 */
class AdviceStatusFactory extends Factory
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
            'name' => fake()->words(3, true),
            'result' => fake()->randomElement(AdviceStatusResult::cases()),
            'group_id' => $group,
        ];
    }
}
