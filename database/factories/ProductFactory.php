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
        $bulkOrderId = $this->faker->randomElement(BulkOrder::pluck("id"));
        if($bulkOrderId === null){
            $bulkOrderId = BulkOrder::factory()->create()->id;
        }

        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 2, 500),
            'sku' => $this->faker->uuid(),
            'panelsCount' => $this->faker->numberBetween(0, 2),
            'bulk_order_id' => $bulkOrderId
        ];
    }

    public function supplierProduct(){
        return $this->state(function(array $attributes){
            return [
                'is_supplier_product' => true
            ];
        });
    }

    public function ownProduct(){
        return $this->state(function(array $attributes){
            return [
                'is_supplier_product' => false
            ];
        });
    }
}
