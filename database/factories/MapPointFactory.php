<?php

namespace Database\Factories;

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
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
        //some random coordinates around Darmstadt
        $lng = 8.6510204;
        $lat = 49.8728475;
        $lng += fake()->randomFloat(null, -1, 1) * 0.1;
        $lat += fake()->randomFloat(null, -1, 1) * 0.1;

        return [
            'lng' => $lng,
            'lat' => $lat,
            'title' => fake()->name(),
            'published' => fake()->boolean(70),
            'description' => fake()->text(256),
        ];
    }

    public function withFormSubmission(): Factory{
        return $this->afterMaking(function (MapPoint $mapPoint){
            $mapPoint->pointable()->associate(FormSubmission::factory()->create());
            return $mapPoint;
        });
    }

    public function withAdvice(): Factory{
        return $this->afterMaking(function (MapPoint $mapPoint){
            $mapPoint->pointable()->associate(Advice::factory()->create());
            return $mapPoint;
        });
    }

    public function withRandomOrNullPointable(): Factory{
        $rand = random_int(1, 3);
        if($rand == 1) {
            return $this->withFormSubmission();
        } else if ($rand == 2){
            return $this->withAdvice();
        }
        return $this;
    }
}
