<?php

namespace Database\Factories;

use App\Models\MapEmbed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapEmbed>
 */
class MapEmbedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // some random coordinates around Darmstadt
        $lng = 8.6510204;
        $lat = 49.8728475;
        $lng += $this->faker->randomFloat(null, -1, 1) * 0.04;
        $lat += $this->faker->randomFloat(null, -1, 1) * 0.04;

        return [
            'name' => $this->faker->words(3, true),
            'lat' => $lat,
            'lng' => $lng,
            'zoom' => $this->faker->numberBetween(3, 18),
        ];
    }
}
