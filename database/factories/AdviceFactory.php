<?php

namespace Database\Factories;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Wnx\Sends\Models\Send;

/**
 * @extends Factory<Advice>
 */
class AdviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {

        if (Group::count() > 0) {
            $group = Group::inRandomOrder()->first();
        } else {
            $group = Group::factory();
        }

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'street' => fake()->streetName(),
            'street_number' => fake()->buildingNumber(),
            'zip' => fake()->numberBetween(10000, 99999),
            'city' => fake()->city(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'commentary' => fake()->optional()->text(),
            'lng' => fake()->optional()->longitude(),
            'lat' => fake()->optional()->latitude(),
            'group_id' => $group,
        ];
    }

    public function withSharing(): self
    {
        return $this->afterCreating(function (Advice $advice) {
            $user = User::factory()->create();
            $advisor = User::factory()->create();
            app(AdviceService::class)->syncShares($advice, collect([$advisor]), $user);
        });
    }

    public function withSendable(): self
    {
        return $this->afterCreating(function (Advice $advice) {
            $sendable = Send::factory()->create();
            $advice->sends()->attach($sendable);
        });
    }

    public function withoutCoordinates(): self
    {
        return $this->state(fn(array $attributes) => [
            'lat' => null,
            'lng' => null,
        ]);
    }
}
