<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\Group;
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
            'group_id' => Group::factory(),
            'lng' => $lng,
            'lat' => $lat,
            'title' => fake()->name(),
            'published' => fake()->boolean(70),
            'description' => fake()->text(256),
            'category_id' => null,
            'location' => fake()->optional(0.7)->address(),
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

    /**
     * Without an explicit category, a new one is created in the point's group,
     * so the point uses a category that is available to its group.
     */
    public function withCategory(?MapPointCategory $category = null): static
    {
        return $this->state(function (array $attributes) use ($category): array {
            if ($category !== null) {
                return ['category_id' => $category->id];
            }

            $category = MapPointCategory::factory()->create(['group_id' => $attributes['group_id']]);

            return [
                'category_id' => $category->id,
                'group_id' => $category->group_id,
            ];
        });
    }
}
