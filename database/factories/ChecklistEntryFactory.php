<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FormType;
use App\Models\Advice;
use App\Models\ChecklistEntry;
use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistEntry>
 */
class ChecklistEntryFactory extends Factory
{
    /**
     * @return array<string, Factory>
     */
    public function definition(): array
    {
        return [
            'form_definition_id' => FormDefinition::factory()->state(['type' => FormType::Checklist]),
            'advice_id' => Advice::factory(),
        ];
    }
}
