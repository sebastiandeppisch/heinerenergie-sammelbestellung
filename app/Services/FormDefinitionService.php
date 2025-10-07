<?php

namespace App\Services;

use App\Data\FormDefinitionData;
use App\Data\FormToAdviceMappingData;
use App\Data\FormToMapPointMappingData;
use App\Data\FormFieldData;
use App\Data\FormFieldOptionData;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormFieldOption as FieldOption;
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
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id'])->toArray();
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

        $creator = $formDefinition->adviceCreator ?: new FormDefinitionToAdvice();
        $creator->formDefinition()->associate($formDefinition);

        $firstName = FormField::where('uuid', $mapping->first_name_field_id)->firstOrFail();
        $lastName = FormField::where('uuid', $mapping->last_name_field_id)->firstOrFail();
        $address = FormField::where('uuid', $mapping->address_field_id)->firstOrFail();
        $email = FormField::where('uuid', $mapping->email_field_id)->firstOrFail();
        $phone = FormField::where('uuid', $mapping->phone_field_id)->firstOrFail();
        $type = FormField::where('uuid', $mapping->advice_type_field_id)->firstOrFail();

        if ($firstName) $creator->firstNameField()->associate($firstName);
        if ($lastName) $creator->lastNameField()->associate($lastName);
        if ($address) $creator->addressField()->associate($address);
        if ($email) $creator->emailField()->associate($email);
        if ($phone) $creator->phoneField()->associate($phone);
        if ($type) $creator->adviceTypeField()->associate($type);

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

        $creator = $formDefinition->mapPointCreator ?: new FormDefinitionToMapPoint();
        $creator->formDefinition()->associate($formDefinition);

        $title = FormField::where('uuid', $mapping->title_field_id)->first();
        $description = FormField::where('uuid', $mapping->description_field_id)->first();
        $coordinate = FormField::where('uuid', $mapping->coordinate_field_id)->first();

        if ($title) $creator->titleField()->associate($title);
        if ($description) $creator->descriptionField()->associate($description);
        if ($coordinate) $creator->coordinateField()->associate($coordinate);

        $creator->save();
    }
}
