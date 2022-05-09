<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'url' => $this->faker->url(),
            'price' => $this->faker->randomFloat(2, 2, 500),
            'sku' => $this->faker->uuid(),
            'panelsCount' => $this->faker->numberBetween(0, 2)
        ];
    }
}
