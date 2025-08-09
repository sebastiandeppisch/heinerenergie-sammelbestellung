<?php

namespace App\Services;

use App\Data\FormDefinitionData;
use App\Models\FormDefinition;
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
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id'])->toArray();
            $formDefinition = FormDefinition::where('uuid', $formDefinitionData->id)->firstOrFail();
            $formDefinition->group_id = Group::where('uuid', $formDefinitionData->group_id)->firstOrFail()->id;
            $formDefinition->update($data);

            $this->updateFields($formDefinitionData->fields, $formDefinition);

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
                $formField = $formDefinition->fields()->insert($data);
            } else {
                $formField->update($data);
            }

            $formFieldIds[] = $formField->id;

            $this->updateFieldOptions($field->options, $formField);
        }
        FormField::whereNotIn('id', $formFieldIds)->delete();
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
        FormFieldOption::whereNotIn('id', $formOptionIds)->delete();
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

            return $formDefinition->fresh();
        });
    }
}
