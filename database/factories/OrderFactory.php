<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\BulkOrder;
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
        $advisorId = $this->faker->randomElement(User::pluck("id"));
        if($advisorId === null){
            $advisorId = User::factory()->create()->id;
        }

        $bulkOrderId = $this->faker->randomElement(BulkOrder::pluck("id"));
        if($bulkOrderId === null){
            $bulkOrderId = BulkOrder::factory()->create()->id;
        }

        return [
            'firstName' =>  $this->faker->firstName(),
            'lastName' =>  $this->faker->lastName(),
            'street' => $this->faker->streetName(),
            'streetNumber' => $this->faker->buildingNumber(),
            'zip' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'advisor_id' => $advisorId,
            'bulk_order_id' => $bulkOrderId
        ];
    }
}
