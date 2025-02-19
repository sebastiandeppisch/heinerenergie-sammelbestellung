<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->optional(0.7)->paragraph(),
            'logo_path' => fake()->optional(0.3)->imageUrl(),
            'parent_id' => null, // By default create main groups, use state method for child groups
            'accepts_transfers' => fake()->boolean(80), // 80% chance of accepting transfers
        ];
    }

    /**
     * Configure the factory to create a child group.
     */
    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Group::factory(),
        ]);
    }
}
