<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormDefinitionToMapPoint>
 */
class FormDefinitionToMapPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleField = FormField::factory()->create([
            'type' => FieldType::TEXT,
            'label' => 'Titel',
            'required' => true,
        ]);

        $descriptionField = FormField::factory()->create([
            'type' => FieldType::TEXTAREA,
            'label' => 'Beschreibung',
            'required' => false,
            'max_length' => 500, // Set a reasonable max length
        ]);

        $coordinateField = FormField::factory()->create([
            'type' => FieldType::GEO_COORDINATE,
            'label' => 'Standort',
            'required' => true,
        ]);

        $formDefinition = FormDefinition::factory()->create();
        $formDefinition->fields()->saveMany([
            $titleField,
            $descriptionField,
            $coordinateField,
        ]);

        return [
            'form_definition_id' => $formDefinition->id,
            'title_field_id' => $titleField->id,
            'description_field_id' => $descriptionField->id,
            'coordinate_field_id' => $coordinateField->id,
        ];
    }

    public function withMapPoint()
    {
        return $this->afterCreating(function (FormDefinitionToMapPoint $creator) {
            $submission = $creator->formDefinition->createSubmission();

            $creator->titleField->createSubmissionField($submission, fake()->sentence(3));

            $creator->descriptionField->createSubmissionField($submission, fake()->sentence(10));

            $creator->coordinateField->createSubmissionField($submission, [
                'lat' => fake()->latitude(),
                'lng' => fake()->longitude(),
            ]);

            $creator->createMapPoint($submission);

            return [];
        });
    }
}
