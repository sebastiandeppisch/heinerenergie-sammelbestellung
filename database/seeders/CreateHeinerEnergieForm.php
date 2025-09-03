<?php

namespace Database\Seeders;

use App\Enums\AdviceType;
use App\Models\FormDefinition;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateHeinerEnergieForm extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::transaction(function () {

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

            $typeField =
            $formDefinition->fields()->create([
                'type' => 'radio',
                'label' => 'Möchtest Du virtuell oder bei Dir vor Ort beraten werden?',
                'required' => true,
            ]);
            $typeField->options()->createMany([
                ['label' => 'Virtuell, per Mail oder Telefon', 'value' => AdviceType::Virtual->value],
                ['label' => 'Vor Ort', 'value' => AdviceType::Home->value],
            ]);
            $formToAdvice->adviceTypeField()->associate($typeField);

            $formDefinition->fields()->create([
                'type' => 'checkbox',
                'label' => 'Bei was benötigst Du Beratung?',
            ])->options()->createMany([
                ['label' => 'Ort (Balkon, Garten, Terrasse, ...)'],
                ['label' => 'Bürokratie (Anmeldung, Förderung, ...)'],
                ['label' => 'Technisches (Anschluss, Befestigung, ...)'],
                ['label' => 'Sonstiges'],
            ]);

            $formDefinition->fields()->create([
                'type' => 'checkbox',
                'label' => 'In was für einem Haus möchtest Du Dein Steckersolargerät installieren?',
            ])->options()->createMany([
                ['label' => 'Einfamilienhaus'],
                ['label' => 'Mehrfamilienhaus'],
                ['label' => 'Sonstiges'],
            ]);

            $formDefinition->fields()->create([
                'type' => 'radio',
                'label' => 'Musst Du bauliche Veränderungen mit einer WEG oder Vermieter*in absprechen?',
            ])->options()->createMany([
                ['label' => 'Ja'],
                ['label' => 'Nein'],
            ]);

            $formDefinition->fields()->create([
                'type' => 'textarea',
                'label' => 'Wo möchtest Du Dein Steckersolargerät installieren?',
            ]);

            $formDefinition->fields()->create([
                'type' => 'textarea',
                'label' => 'Hast Du sonst noch Fragen oder möchtest ein Kommentar hinzufügen?',
            ]);

            $formToAdvice->default_group_id = $formDefinition->group_id;

            $formToAdvice->save();
        });
    }
}
