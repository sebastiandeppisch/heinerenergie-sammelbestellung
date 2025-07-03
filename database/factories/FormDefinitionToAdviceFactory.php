<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormDefinitionToAdvice>
 */
class FormDefinitionToAdviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $mailField = FormField::factory()->create([
            'type' => FieldType::EMAIL,
            'label' => 'E-Mail',
            'required' => true,
        ]);

        $addressField = FormField::factory()->create([
            'type' => FieldType::ADDRESS,
            'label' => 'Address',
            'required' => true,
        ]);

        $phoneField = FormField::factory()->create([
            'type' => FieldType::PHONE,
            'label' => 'Phone Number',
            'required' => true,
        ]);

        $firstNameField = FormField::factory()->create([
            'type' => FieldType::TEXT,
            'label' => 'First Name',
            'required' => true,
        ]);

        $lastNameField = FormField::factory()->create([
            'type' => FieldType::TEXT,
            'label' => 'Last Name',
            'required' => true,
        ]);

        $formDefinition = FormDefinition::factory()->create();
        $formDefinition->fields()->saveMany([
            $mailField,
            $addressField,
            $phoneField,
            $firstNameField,
            $lastNameField,
        ]);

        return [
            'form_definition_id' => $formDefinition->id,
            'address_field_id' => $addressField->id,
            'email_field_id' => $mailField->id,
            'phone_field_id' => $phoneField->id,
            'first_name_field_id' => $firstNameField->id,
            'last_name_field_id' => $lastNameField->id,
            'default_group_id' => Group::factory(),
        ];
    }
}
