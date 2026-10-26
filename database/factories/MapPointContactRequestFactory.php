<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MapPoint;
use App\Models\MapPointContactRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MapPointContactRequest>
 */
class MapPointContactRequestFactory extends Factory
{
    protected $model = MapPointContactRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'map_point_id' => MapPoint::factory(),
            'sender_name' => fake()->name(),
            'sender_email' => fake()->safeEmail(),
            'message' => fake()->text(200),
            'confirmation_token_hash' => hash('sha256', Str::random(64)),
            'confirmation_expires_at' => now()->addDay(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'confirmed_at' => now(),
            'forwarded_at' => now(),
        ]);
    }
}
