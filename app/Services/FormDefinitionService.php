<?php

namespace App\Services;

use App\Data\FormDefinitionData;
use App\Data\FormFieldData;
use App\Data\FormFieldOptionData;
use App\Data\FormToAdviceMappingData;
use App\Data\FormToMapPointMappingData;
use App\Enums\AdviceType;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FormDefinitionService
{
    public function updateFormDefinitionData(FormDefinitionData $formDefinitionData): FormDefinition
    {
        return DB::transaction(function () use ($formDefinitionData) {
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id', 'advice_mapping', 'map_point_mapping'])->toArray();
            $formDefinition = FormDefinition::where('uuid', $formDefinitionData->id)->firstOrFail();
            $formDefinition->group_id = Group::where('uuid', $formDefinitionData->group_id)->firstOrFail()->id;

            $formDefinition->update($data);

            $this->updateFields($formDefinitionData->fields, $formDefinition);

            $this->updateAdviceMapping($formDefinition, $formDefinitionData->advice_mapping);
            $this->updateMapPointMapping($formDefinition, $formDefinitionData->map_point_mapping);

            return $formDefinition->fresh();
        });
    }

    /**
     * @param  Collection<FormFieldData>  $fields
     */
    private function updateFields(Collection $fields, FormDefinition $formDefinition)
    {
        $formFieldIds = [];
        foreach ($fields as $field) {
            $data = collect($field->toArray())->forget(['id', 'options', 'form_definition_id'])->toArray();

            $formField = FormField::where('uuid', $field->id)->first();

            if ($formField === null) {
                $formField = $formDefinition->fields()->create($data);
            } else {
                $formField->update($data);
            }

            $formFieldIds[] = $formField->id;

            $this->updateFieldOptions($field->options, $formField);
        }
        FormField::whereNotIn('id', $formFieldIds)->get()->each->delete();
    }

    /**
     * @param  Collection<FormFieldOptionData>  $options
     */
    private function updateFieldOptions(Collection $options, FormField $formField): void
    {
        $formOptionIds = [];
        foreach ($options as $option) {

            $data = collect($option)->forget(['id'])->toArray();

            $option = FormFieldOption::where('uuid', $option->id)->first();
            if ($option === null) {
                $option = $formField->options()->create($data);
            } else {
                $option->update($data);
            }

            $formOptionIds[] = $option->id;
        }
        FormFieldOption::whereNotIn('id', $formOptionIds)->get()->each->delete();
    }

    public function storeFormDefinitionData(FormDefinitionData $formDefinitionData): FormDefinition
    {
        return DB::transaction(function () use ($formDefinitionData) {
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id', 'advice_mapping', 'map_point_mapping'])->toArray();
            $data['group_id'] = Group::where('uuid', $formDefinitionData->group_id)->firstOrFail()->id;
            $formDefinition = FormDefinition::create($data);
            foreach ($formDefinitionData->fields as $field) {
                $data = collect($field->toArray())->forget(['id', 'options', 'form_definition_id'])->toArray();
                $formField = $formDefinition->fields()->create($data);
                foreach ($field->options as $option) {
                    $data = collect($option)->forget(['id'])->toArray();
                    $formField->options()->create($data);
                }
            }

            $this->updateAdviceMapping($formDefinition, $formDefinitionData->advice_mapping);
            $this->updateMapPointMapping($formDefinition, $formDefinitionData->map_point_mapping);

            return $formDefinition->fresh();
        });
    }

    private function updateAdviceMapping(FormDefinition $formDefinition, ?FormToAdviceMappingData $mapping): void
    {
        if ($mapping === null || $mapping->enabled === false) {
            if ($formDefinition->adviceCreator) {
                $formDefinition->adviceCreator->delete();
            }

            return;
        }

        $creator = $formDefinition->adviceCreator ?: new FormDefinitionToAdvice;
        $creator->formDefinition()->associate($formDefinition);

        $firstName = FormField::where('uuid', $mapping->first_name_field_id)->firstOrFail();
        $lastName = FormField::where('uuid', $mapping->last_name_field_id)->firstOrFail();
        $address = FormField::where('uuid', $mapping->address_field_id)->firstOrFail();
        $email = FormField::where('uuid', $mapping->email_field_id)->firstOrFail();
        $phone = FormField::where('uuid', $mapping->phone_field_id)->firstOrFail();
        $type = FormField::where('uuid', $mapping->advice_type_field_id)->firstOrFail();

        if ($firstName) {
            $creator->firstNameField()->associate($firstName);
        }
        if ($lastName) {
            $creator->lastNameField()->associate($lastName);
        }
        if ($address) {
            $creator->addressField()->associate($address);
        }
        if ($email) {
            $creator->emailField()->associate($email);
        }
        if ($phone) {
            $creator->phoneField()->associate($phone);
        }
        if ($type) {
            $creator->adviceTypeField()->associate($type);
        }

        if ($mapping->advice_type_home_option_value) {
            $creator->advice_type_home_option_value = $mapping->advice_type_home_option_value;
        }

        if ($mapping->advice_type_virtual_option_value) {
            $creator->advice_type_virtual_option_value = $mapping->advice_type_virtual_option_value;
        }

        if ($mapping->default_group_id) {
            $creator->default_group_id = optional(Group::where('uuid', $mapping->default_group_id)->first())->id;
        } else {
            $creator->default_group_id = $formDefinition->group_id;
        }

        $creator->save();
    }

    private function updateMapPointMapping(FormDefinition $formDefinition, ?FormToMapPointMappingData $mapping): void
    {
        if ($mapping === null || $mapping->enabled === false) {
            if ($formDefinition->mapPointCreator) {
                $formDefinition->mapPointCreator->delete();
            }

            return;
        }

        $creator = $formDefinition->mapPointCreator ?: new FormDefinitionToMapPoint;
        $creator->formDefinition()->associate($formDefinition);

        $title = FormField::where('uuid', $mapping->title_field_id)->first();
        $description = FormField::where('uuid', $mapping->description_field_id)->first();
        $coordinate = FormField::where('uuid', $mapping->coordinate_field_id)->first();

        if ($title) {
            $creator->titleField()->associate($title);
        }
        if ($description) {
            $creator->descriptionField()->associate($description);
        }
        if ($coordinate) {
            $creator->coordinateField()->associate($coordinate);
        }

        $creator->save();
    }

    /**
     * Create a FormDefinition from a template
     */
    public function createFromTemplate(string $templateType, string $groupUuid): FormDefinition
    {
        return DB::transaction(function () use ($templateType, $groupUuid) {
            $group = Group::where('uuid', $groupUuid)->firstOrFail();

            return match ($templateType) {
                'advice' => $this->createAdviceFormTemplate($group),
                'map_point' => throw new \InvalidArgumentException('Map Point template not yet implemented'),
                default => throw new \InvalidArgumentException("Unknown template type: {$templateType}"),
            };
        });
    }

    /**
     * Create an Advice Form from template (like CreateAdviceForm seeder)
     */
    private function createAdviceFormTemplate(Group $group): FormDefinition
    {
        $formDefinition = new FormDefinition;
        $formDefinition->name = 'Beratungsformular für '.$group->name;
        $formDefinition->group()->associate($group);
        $formDefinition->is_active = true;
        $formDefinition->save();

        // Create form fields
        $firstNameField = $formDefinition->fields()->create([
            'type' => 'text',
            'label' => 'Vorname',
            'min_length' => 1,
            'max_length' => 255,
            'placeholder' => 'Vorname',
            'required' => true,
            'sort_order' => 0,
        ]);

        $lastNameField = $formDefinition->fields()->create([
            'type' => 'text',
            'label' => 'Nachname',
            'min_length' => 1,
            'max_length' => 255,
            'placeholder' => 'Nachname',
            'required' => true,
            'sort_order' => 1,
        ]);

        $addressField = $formDefinition->fields()->create([
            'type' => 'address',
            'label' => 'Adresse',
            'required' => true,
            'sort_order' => 2,
        ]);

        $emailField = $formDefinition->fields()->create([
            'type' => 'email',
            'label' => 'E-Mail Adresse',
            'max_length' => 255,
            'placeholder' => 'E-Mail',
            'required' => true,
            'sort_order' => 3,
        ]);

        $phoneField = $formDefinition->fields()->create([
            'type' => 'phone',
            'label' => 'Telefonnummer',
            'max_length' => 255,
            'placeholder' => 'Telefonnummer',
            'required' => true,
            'sort_order' => 4,
        ]);

        $typeField = $formDefinition->fields()->create([
            'type' => 'radio',
            'label' => 'Möchtest Du virtuell oder bei Dir vor Ort beraten werden?',
            'required' => true,
            'sort_order' => 5,
        ]);

        $typeField->options()->createMany([
            [
                'label' => 'Virtuell, per Mail oder Telefon',
                'value' => (string) AdviceType::Virtual->value,
                'sort_order' => 0,
                'is_default' => false,
            ],
            [
                'label' => 'Vor Ort',
                'value' => (string) AdviceType::Home->value,
                'sort_order' => 1,
                'is_default' => false,
            ],
        ]);

        // Create FormDefinitionToAdvice mapping
        $formToAdvice = $formDefinition->adviceCreator()->make();
        $formToAdvice->firstNameField()->associate($firstNameField);
        $formToAdvice->lastNameField()->associate($lastNameField);
        $formToAdvice->addressField()->associate($addressField);
        $formToAdvice->emailField()->associate($emailField);
        $formToAdvice->phoneField()->associate($phoneField);
        $formToAdvice->adviceTypeField()->associate($typeField);
        $formToAdvice->advice_type_home_option_value = (string) AdviceType::Home->value;
        $formToAdvice->advice_type_virtual_option_value = (string) AdviceType::Virtual->value;
        $formToAdvice->default_group_id = $formDefinition->group_id;
        $formToAdvice->save();

        return $formDefinition->fresh();
    }
}
