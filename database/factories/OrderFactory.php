<?php

namespace Database\Factories;

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
        return [
            'firstName' =>  $this->faker->firstName(),
            'lastName' =>  $this->faker->lastName(),
            'street' => $this->faker->streetName(),
            'streetNumber' => $this->faker->buildingNumber(),
            'zip' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber()
        ];
    }
}
