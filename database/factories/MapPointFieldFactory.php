<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\MapPoint;
use App\Models\MapPointField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPointField>
 */
class MapPointFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'map_point_id' => MapPoint::factory(),
            'form_field_id' => FormField::factory(),
            'value' => $this->faker->word(),
            'type' => FieldType::TEXT->value,
            'label' => ucfirst($this->faker->unique()->word()),
            'sort_order' => 0,
        ];
    }
}
