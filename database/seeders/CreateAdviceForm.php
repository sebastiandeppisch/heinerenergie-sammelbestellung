<?php

namespace Database\Seeders;

use App\Enums\AdviceType;
use App\Models\FormDefinition;
use App\Models\Group;
use Illuminate\Database\Seeder;

class CreateAdviceForm extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = Group::firstOrFail();
        $formDefinition = new FormDefinition;
        $formDefinition->name = 'Beratungsformular für '.$group->name;
        $formDefinition->group()->associate($group);
        $formDefinition->save();

        $formToAdvice = $formDefinition->adviceCreator()->make();

        $formToAdvice->firstNameField()->associate($formDefinition->fields()->create([
            'type' => 'text',
            'label' => 'Vorname',
            'min_length' => 1,
            'max_length' => 255,
            'placeholder' => 'Vorname',
            'required' => true,
        ]));

        $formToAdvice->lastNameField()->associate($formDefinition->fields()->create([
            'type' => 'text',
            'label' => 'Nachname',
            'min_length' => 1,
            'max_length' => 255,
            'placeholder' => 'Nachname',
            'required' => true,
        ]));

        $formToAdvice->addressField()->associate($formDefinition->fields()->create([
            'type' => 'address',
            'label' => 'Adresse',
            'required' => true,
        ]));

        $formToAdvice->emailField()->associate($formDefinition->fields()->create([
            'type' => 'email',
            'label' => 'E-Mail Adresse',
            'max_length' => 255,
            'placeholder' => 'E-Mail',
            'required' => true,
        ]));

        $formToAdvice->phoneField()->associate($formDefinition->fields()->create([
            'type' => 'phone',
            'label' => 'Telefonnummer',
            'max_length' => 255,
            'placeholder' => 'Telefonnummer',
            'required' => true,
        ]));

        $typeField = $formDefinition->fields()->create([
            'type' => 'radio',
            'label' => 'Möchtest Du virtuell oder bei Dir vor Ort beraten werden?',
            'required' => true,
        ]);
        $typeField->options()->createMany([
            ['label' => 'Virtuell, per Mail oder Telefon', 'value' => AdviceType::Virtual->value],
            ['label' => 'Vor Ort', 'value' => AdviceType::Home->value],
        ]);
        $formToAdvice->adviceTypeField()->associate($typeField);

        $formToAdvice->default_group_id = $formDefinition->group_id;

        $formToAdvice->save();
    }
}
