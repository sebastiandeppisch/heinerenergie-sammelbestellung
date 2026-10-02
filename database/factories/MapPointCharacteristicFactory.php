<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPointCharacteristic>
 */
class MapPointCharacteristicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'map_point_category_id' => MapPointCategory::factory(),
            'name' => $this->faker->randomElement(['Igeltor', 'Totholz', 'Speicher', 'Nisthilfe', 'Blühstreifen']),
            'icon_path' => null,
            'color' => $this->faker->optional()->hexColor(),
            'sort_order' => 0,
        ];
    }
}
