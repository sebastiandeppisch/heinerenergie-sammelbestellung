<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPointSpreadsheetMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPointSpreadsheetMapping>
 */
class MapPointSpreadsheetMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->unique()->words(2, true),
            'columns' => [
                ['header' => 'ID', 'field' => MapPointSpreadsheetField::ID->value],
                ['header' => 'Titel', 'field' => MapPointSpreadsheetField::TITLE->value],
                ['header' => 'Breitengrad', 'field' => MapPointSpreadsheetField::LATITUDE->value],
                ['header' => 'Längengrad', 'field' => MapPointSpreadsheetField::LONGITUDE->value],
            ],
            'key_field' => MapPointSpreadsheetField::ID,
        ];
    }
}
