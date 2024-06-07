<?php

namespace Database\Factories;

use App\Models\BulkOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $advisorId = fake()->randomElement(User::pluck('id'));
        if ($advisorId === null) {
            $advisorId = User::factory()->create()->id;
        }

        $bulkOrderId = fake()->randomElement(BulkOrder::pluck('id'));
        if ($bulkOrderId === null) {
            $bulkOrderId = BulkOrder::factory()->create()->id;
        }

        return [
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'street' => fake()->streetName(),
            'streetNumber' => fake()->buildingNumber(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'advisor_id' => $advisorId,
            'bulk_order_id' => $bulkOrderId,
        ];
    }
}
