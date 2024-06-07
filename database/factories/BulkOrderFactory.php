<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BulkOrderFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'archived' => false, //$this->faker->boolean
        ];
    }
}
