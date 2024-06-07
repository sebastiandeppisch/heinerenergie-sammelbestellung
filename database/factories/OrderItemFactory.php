<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $productId = fake()->randomElement(Product::pluck('id'));
        if ($productId === null) {
            $productId = Product::factory()->create()->id;
        }

        return [
            'product_id' => $productId,
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
