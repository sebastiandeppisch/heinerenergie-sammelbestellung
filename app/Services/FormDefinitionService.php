<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FormDefinitionData;
use App\Data\FormFieldData;
use App\Data\FormFieldOptionData;
use App\Data\FormToAdviceMappingData;
use App\Data\FormToMapPointFieldData;
use App\Data\FormToMapPointMappingData;
use App\Data\FormToMapPointSubcategoryData;
use App\Enums\AdviceType;
use App\Enums\FieldType;
use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\MapPointCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FormDefinitionService
{
    public function updateFormDefinitionData(FormDefinitionData $formDefinitionData): FormDefinition
    {
        return DB::transaction(function () use ($formDefinitionData) {
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id', 'advice_mapping', 'map_point_mapping'])->toArray();
            $formDefinition = FormDefinition::where('uuid', $formDefinitionData->id)->firstOrFail();

            // Fields of a map point category belong to the category's group.
            if ($formDefinition->type !== FormType::MapPointFields) {
                $formDefinition->group_id = Group::where('uuid', $formDefinitionData->group_id)->firstOrFail()->id;
            }

            $formDefinition->update($data);

            $this->updateFields($formDefinitionData->fields, $formDefinition);

            $this->updateAdviceMapping($formDefinition, $formDefinitionData->advice_mapping);
            $this->updateMapPointMapping($formDefinition, $formDefinitionData->map_point_mapping);

            return $formDefinition->fresh();
        });
    }

    /**
     * @param  Collection<int, FormFieldData>  $fields
     */
    private function updateFields(Collection $fields, FormDefinition $formDefinition): void
    {
        $formFieldIds = [];
        foreach ($fields as $field) {
            $data = collect($field->toArray())->forget(['id', 'options', 'form_definition_id'])->toArray();

            if ($formDefinition->type === FormType::MapPointFields) {
                // Values of category fields are always optional, so points never become invalid when fields change.
                $data['required'] = false;
            }

            $uuid = $this->toUuidOrNull($field->id);

            $formField = $uuid === null ? null : FormField::where('uuid', $uuid)->first();

            if ($formField === null) {
                $formField = $formDefinition->fields()->make($data);
                $formField->uuid = $uuid;
                $formField->save();
            } else {
                $formField->update($data);
            }

            $formFieldIds[] = $formField->id;

            $this->updateFieldOptions($field->options, $formField, allowRequired: $formDefinition->type !== FormType::MapPointFields);
        }

        FormField::where('form_definition_id', $formDefinition->id)->whereNotIn('id', $formFieldIds)->get()->each->delete();
    }

    /**
     * @param  Collection<int, FormFieldOptionData>  $options
     */
    private function updateFieldOptions(Collection $options, FormField $formField, bool $allowRequired = true): void
    {
        $formOptionIds = [];
        foreach ($options as $option) {

            $data = collect($option)->forget(['id'])->toArray();

            if (! $allowRequired) {
                $data['is_required'] = false;
            }

            $uuid = $this->toUuidOrNull($option->id);

            $option = $uuid === null ? null : FormFieldOption::where('uuid', $uuid)->first();
            if ($option === null) {
                $option = $formField->options()->create($data);
            } else {
                $option->update($data);
            }

            $formOptionIds[] = $option->id;
        }
        FormFieldOption::where('form_field_id', $formField->id)->whereNotIn('id', $formOptionIds)->get()->each->delete();
    }

    /**
     * The client sends placeholder ids for records that do not exist yet. Querying or storing them
     * would blow up on postgres uuid columns, so anything that is not a uuid is treated as "new".
     */
    private function toUuidOrNull(?string $id): ?string
    {
        if ($id === null || ! Str::isUuid($id)) {
            return null;
        }

        return $id;
    }

    public function storeFormDefinitionData(FormDefinitionData $formDefinitionData): FormDefinition
    {
        return DB::transaction(function () use ($formDefinitionData) {
            $data = collect($formDefinitionData->toArray())->forget(['id', 'fields', 'group_id', 'advice_mapping', 'map_point_mapping'])->toArray();
            $data['group_id'] = Group::where('uuid', $formDefinitionData->group_id)->firstOrFail()->id;
            $formDefinition = FormDefinition::create($data);
            foreach ($formDefinitionData->fields as $field) {
                $data = collect($field->toArray())->forget(['id', 'options', 'form_definition_id'])->toArray();
                // Keeping the id the client sent lets the mappings under "Ziele" refer to fields that are created now.
                $formField = $formDefinition->fields()->make($data);
                $formField->uuid = $this->toUuidOrNull($field->id);
                $formField->save();
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

        // Handle advice type: either direct or via field
        if ($mapping->advice_type_direct !== null) {
            // Direct type is set
            $creator->advice_type_direct = $mapping->advice_type_direct;
            $creator->advice_type_field_id = null;
            // Clear option values when using direct type (will be nullable after migration)
            $creator->advice_type_home_option_value = '';
            $creator->advice_type_virtual_option_value = '';
        } elseif ($mapping->advice_type_field_id !== null) {
            // Field is set
            $type = FormField::where('uuid', $mapping->advice_type_field_id)->firstOrFail();
            $creator->adviceTypeField()->associate($type);
            $creator->advice_type_direct = null;

            if ($mapping->advice_type_home_option_value) {
                $creator->advice_type_home_option_value = $mapping->advice_type_home_option_value;
            }

            if ($mapping->advice_type_virtual_option_value) {
                $creator->advice_type_virtual_option_value = $mapping->advice_type_virtual_option_value;
            }
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

        $category = $mapping->category_id === null ? null : MapPointCategory::where('uuid', $mapping->category_id)->first();
        $subcategoryField = $category === null || $mapping->subcategory_field_id === null
            ? null
            : FormField::where('uuid', $mapping->subcategory_field_id)->first();

        $creator->category()->associate($category);
        $creator->subcategoryField()->associate($subcategoryField);
        $creator->save();

        $this->updateMapPointSubcategories($creator, $subcategoryField === null ? new Collection : $mapping->subcategory_options);
        $this->updateMapPointFieldMappings($creator, $category === null ? new Collection : $mapping->field_mappings);
    }

    /**
     * @param  Collection<int, FormToMapPointSubcategoryData>  $options
     */
    private function updateMapPointSubcategories(FormDefinitionToMapPoint $creator, Collection $options): void
    {
        $creator->subcategories()->delete();
        $categoryIds = MapPointCategory::whereIn('uuid', $options->pluck('category_id'))->pluck('id', 'uuid');

        foreach ($options as $option) {
            $creator->subcategories()->create([
                'option_value' => $option->option_value,
                'map_point_category_id' => $categoryIds[$option->category_id],
            ]);
        }
    }

    /**
     * @param  Collection<int, FormToMapPointFieldData>  $fieldMappings
     */
    private function updateMapPointFieldMappings(FormDefinitionToMapPoint $creator, Collection $fieldMappings): void
    {
        $creator->fieldMappings()->delete();
        $fieldIds = FormField::whereIn('uuid', [...$fieldMappings->pluck('target_field_id'), ...$fieldMappings->pluck('source_field_id')])->pluck('id', 'uuid');

        foreach ($fieldMappings as $fieldMapping) {
            $creator->fieldMappings()->create([
                'target_field_id' => $fieldIds[$fieldMapping->target_field_id],
                'source_field_id' => $fieldIds[$fieldMapping->source_field_id],
            ]);
        }
    }

    /**
     * Create a FormDefinition from a template
     */
    /**
     * @param  MapPointCategory|null  $mapPointCategory  Only for map point forms: the category of the points, whose fields the form asks for.
     */
    public function createFromTemplate(string $templateType, string $groupUuid, ?MapPointCategory $mapPointCategory = null): FormDefinition
    {
        return DB::transaction(function () use ($templateType, $groupUuid, $mapPointCategory): FormDefinition {
            $group = Group::where('uuid', $groupUuid)->firstOrFail();

            return match ($templateType) {
                'advice' => $this->createAdviceFormTemplate($group),
                'map_point' => $this->createMapPointFormTemplate($group, $mapPointCategory),
                default => throw new InvalidArgumentException("Unknown template type: {$templateType}"),
            };
        });
    }

    /**
     * A form that creates map points: title, description and location, and with a category one question per field
     * of the category, already mapped to it. The form fields copy type, label and options of the category fields.
     */
    private function createMapPointFormTemplate(Group $group, ?MapPointCategory $category): FormDefinition
    {
        $formDefinition = new FormDefinition;
        $formDefinition->name = $category === null ? 'Kartenpunkt-Formular für '.$group->name : 'Formular für '.$category->name;
        $formDefinition->group()->associate($group);
        $formDefinition->is_active = true;
        $formDefinition->save();

        $titleField = $formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Titel', 'max_length' => 255, 'required' => true, 'sort_order' => 0]);
        $descriptionField = $formDefinition->fields()->create(['type' => FieldType::TEXTAREA, 'label' => 'Beschreibung', 'required' => false, 'sort_order' => 1]);
        $coordinateField = $formDefinition->fields()->create(['type' => FieldType::GEO_COORDINATE, 'label' => 'Standort', 'required' => true, 'sort_order' => 2]);

        $creator = $formDefinition->mapPointCreator()->make();
        $creator->titleField()->associate($titleField);
        $creator->descriptionField()->associate($descriptionField);
        $creator->coordinateField()->associate($coordinateField);
        $creator->category()->associate($category);
        $creator->save();

        $sortOrder = 3;

        foreach (app(MapPointFieldService::class)->fieldsOfCategory($category?->id) as $categoryField) {
            $formField = $formDefinition->fields()->create([
                'type' => $categoryField->type,
                'label' => $categoryField->label,
                'help_text' => $categoryField->help_text,
                'placeholder' => $categoryField->placeholder,
                'min_length' => $categoryField->min_length,
                'max_length' => $categoryField->max_length,
                'min_value' => $categoryField->min_value,
                'max_value' => $categoryField->max_value,
                'required' => false,
                'sort_order' => $sortOrder++,
            ]);

            foreach ($categoryField->options as $option) {
                $formField->options()->create(['label' => $option->label, 'value' => $option->value, 'sort_order' => $option->sort_order]);
            }

            $creator->fieldMappings()->create(['target_field_id' => $categoryField->id, 'source_field_id' => $formField->id]);
        }

        return $formDefinition->fresh();
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
