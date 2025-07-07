<?php

namespace Database\Seeders;

use App\Models\FormDefinition;
use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateMapPointForm extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = Group::firstOrFail();
        $formDefinition = new FormDefinition();
        $formDefinition->name = 'Kartenpunktformular für '.$group->name;
        $formDefinition->group()->associate($group);
        $formDefinition->save();

        $formToMapPoint = $formDefinition->mapPointCreator()->make();

        $formToMapPoint->titleField()->associate($formDefinition->fields()->create([
            'type' => 'text',
            'label' => 'Titel',
            'min_length' => 1,
            'max_length' => 255,
            'placeholder' => 'Titel',
            'required' => true,
        ]));

        $formToMapPoint->descriptionField()->associate($formDefinition->fields()->create([
            'type' => 'textarea',
            'label' => 'Beschreibung',
            'min_length' => 1,
            'max_length' => 1000,
            'placeholder' => 'Beschreibung',
            'required' => true,
        ]));

        $formToMapPoint->coordinateField()->associate($formDefinition->fields()->create([
            'type' => 'geo_coordinate',
            'label' => 'Koordinaten',
            'required' => true
        ]));

        $formToMapPoint->save();
    }
}
