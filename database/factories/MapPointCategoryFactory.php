<?php

namespace Database\Factories;

use App\Models\MapPointCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPointCategory>
 */
class MapPointCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'image_path' => $this->faker->optional(0.7)->randomElement([
                'categories/pin-red.png',
                'categories/pin-blue.png',
                'categories/pin-green.png',
                'categories/pin-yellow.png',
            ]),
        ];
    }

    /**
     * Category without image
     */
    public function withoutImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => null,
        ]);
    }

    /**
     * Category with specific image
     */
    public function withImage(string $imagePath): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => $imagePath,
        ]);
    }
}
