<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition()
    {
        $fieldTypes = [
            FieldType::TEXT->value,
            FieldType::TEXTAREA->value,
            FieldType::NUMBER->value,
            FieldType::EMAIL->value,
            FieldType::PHONE->value,
            FieldType::SELECT->value,
            FieldType::RADIO->value,
            FieldType::CHECKBOX->value,
            FieldType::DATE->value,
            FieldType::FILE->value,
        ];

        $type = $this->faker->randomElement($fieldTypes);

        return [
            'type' => $type,
            'label' => ucfirst($this->faker->unique()->word),
            'placeholder' => $this->faker->optional(0.7)->sentence(2),
            'help_text' => $this->faker->optional(0.4)->sentence(),
            'required' => $this->faker->boolean(30) && $type !== FieldType::CHECKBOX->value,
            'default_value' => $this->faker->optional(0.2)->word(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'min_length' => $this->faker->optional(0.3)->numberBetween(1, 5),
            'max_length' => $this->faker->optional(0.3)->numberBetween(50, 255),
            'min_value' => in_array($type, [FieldType::NUMBER->value]) ?
                $this->faker->optional(0.3)->numberBetween(0, 10) : null,
            'max_value' => in_array($type, [FieldType::NUMBER->value]) ?
                $this->faker->optional(0.3)->numberBetween(100, 1000) : null,
            'accepted_file_types' => $type === FieldType::FILE->value ?
                $this->faker->randomElements(['.jpg', '.png', '.pdf', '.docx'], 2) : null,
            'form_definition_id' => FormDefinition::factory(),
        ];
    }

    #[\Override]
    public function configure()
    {
        return $this->afterCreating(function (FormField $formField) {
            if (in_array($formField->type, [FieldType::SELECT, FieldType::RADIO, FieldType::CHECKBOX])) {

                $count = random_int(2, 5);
                $defaultOptionIndex = random_int(0, $count - 1);

                for ($i = 0; $i < $count; $i++) {
                    $formField->options()->create([
                        'label' => ucfirst($this->faker->word),
                        'value' => strtolower($this->faker->unique()->word).'_'.$this->faker->numberBetween(1, 100),
                        'sort_order' => $i,
                        'is_default' => $i === $defaultOptionIndex && $this->faker->boolean(70),
                    ]);
                }
            }

        });
    }
}
