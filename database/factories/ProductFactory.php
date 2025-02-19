<?php

namespace Database\Factories;

use App\Models\BulkOrder;
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
        $bulkOrderId = fake()->randomElement(BulkOrder::pluck('id'));
        if ($bulkOrderId === null) {
            $bulkOrderId = BulkOrder::factory()->create()->id;
        }

        return [
            'name' => fake()->word(),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 2, 500),
            'sku' => fake()->uuid(),
            'panelsCount' => fake()->numberBetween(0, 2),
            'bulk_order_id' => $bulkOrderId,
        ];
    }

    public function supplierProduct()
    {
        return $this->state(fn(array $attributes) => [
            'is_supplier_product' => true,
        ]);
    }

    public function ownProduct()
    {
        return $this->state(fn(array $attributes) => [
            'is_supplier_product' => false,
        ]);
    }
}
