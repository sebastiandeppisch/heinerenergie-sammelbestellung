<?php

declare(strict_types=1);

namespace Database\Factories;

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\Models\HexNet;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HexNet>
 */
class HexNetFactory extends Factory
{
    protected $model = HexNet::class;

    public function definition(): array
    {
        $name = 'HexaNetz '.fake()->unique()->city();

        return [
            'group_id' => Group::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => HexNetStatus::DRAFT,
            'title' => $name,
            'hex_radius_m' => 150,
            'threshold_medium' => 2,
            'threshold_dark' => 3,
            'jitter_m' => 40,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => HexNetStatus::ACTIVE]);
    }

    /**
     * A square of about 1.4 km around the origin in Darmstadt.
     */
    public function withPlayArea(float $lat = 49.8728, float $lng = 8.6512, float $halfSizeDeg = 0.01): static
    {
        return $this->state(fn (): array => [
            'grid_origin_lat' => $lat,
            'grid_origin_lng' => $lng,
            'play_area' => ['coordinates' => [
                ['lat' => $lat - $halfSizeDeg, 'lng' => $lng - $halfSizeDeg],
                ['lat' => $lat - $halfSizeDeg, 'lng' => $lng + $halfSizeDeg],
                ['lat' => $lat + $halfSizeDeg, 'lng' => $lng + $halfSizeDeg],
                ['lat' => $lat + $halfSizeDeg, 'lng' => $lng - $halfSizeDeg],
            ]],
        ]);
    }
}
