<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPoint>
 */
class MapPointFactory extends Factory
{
    protected $model = MapPoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // some random coordinates around Darmstadt
        $lng = 8.6510204;
        $lat = 49.8728475;
        $lng += fake()->randomFloat(null, -1, 1) * 0.04;
        $lat += fake()->randomFloat(null, -1, 1) * 0.04;

        return [
            'lng' => $lng,
            'lat' => $lat,
            'title' => fake()->name(),
            'published' => fake()->boolean(70),
            'description' => fake()->text(256),
            'category_id' => null,
        ];
    }

    public function withFormSubmission(): Factory
    {
        return $this->afterMaking(function (MapPoint $mapPoint): MapPoint {
            $mapPoint->pointable()->associate(FormSubmission::factory()->create());

            return $mapPoint;
        });
    }

    public function withAdvice(): Factory
    {
        return $this->afterMaking(function (MapPoint $mapPoint): MapPoint {
            $mapPoint->pointable()->associate(Advice::factory()->create());

            return $mapPoint;
        });
    }

    public function withRandomOrNullPointable(): Factory
    {
        $rand = random_int(1, 3);
        if ($rand == 1) {
            return $this->withFormSubmission();
        }
        if ($rand == 2) {
            return $this->withAdvice();
        }

        return $this;
    }

    public function withCategory($category = null): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $category?->id ?? MapPointCategory::factory()->create()->id,
        ]);
    }
}
