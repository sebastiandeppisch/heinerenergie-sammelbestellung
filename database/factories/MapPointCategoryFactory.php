<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
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
            'group_id' => Group::factory(),
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
     * Sub category of the given category, in the same group.
     */
    public function childOf(MapPointCategory $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'group_id' => $parent->group_id,
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Category without image
     */
    public function withoutImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'image_path' => null,
        ]);
    }

    /**
     * Category with specific image
     */
    public function withImage(string $imagePath): static
    {
        return $this->state(fn (array $attributes): array => [
            'image_path' => $imagePath,
        ]);
    }
}
