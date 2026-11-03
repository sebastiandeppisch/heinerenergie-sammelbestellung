<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\Enums\FieldType;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Services\FormDefinitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates the categories, measures and the join form of a HexaNetz in one step.
 */
class HexNetSetupService
{
    public function __construct(private readonly FormDefinitionService $formDefinitionService) {}

    /**
     * A main category with the measures and a subcategory for this HexaNetz, which becomes its category.
     *
     * Each new measure gets its placeholder icon and an optional, public photo field.
     *
     * @param  array<int, array{name: string, color: string, icon?: string|null}>  $measures
     */
    public function createCategories(HexNet $hexNet, string $mainName, string $subName, array $measures): MapPointCategory
    {
        return DB::transaction(function () use ($hexNet, $mainName, $subName, $measures): MapPointCategory {
            $main = MapPointCategory::firstOrCreate(
                ['group_id' => $hexNet->group_id, 'parent_id' => null, 'name' => $mainName],
            );

            $existing = $main->characteristics()->pluck('name')->all();
            $sortOrder = $main->characteristics()->max('sort_order') ?? -1;

            foreach ($measures as $measure) {
                if (! in_array($measure['name'], $existing, true)) {
                    $characteristic = $main->characteristics()->create([
                        'name' => $measure['name'],
                        'color' => $measure['color'],
                        'icon_path' => $this->storeIcon($measure['icon'] ?? null),
                        'sort_order' => ++$sortOrder,
                    ]);
                    $this->addPhotoField($characteristic);
                }
            }

            $sub = MapPointCategory::firstOrCreate(
                ['group_id' => $hexNet->group_id, 'parent_id' => $main->id, 'name' => $subName],
            );
            $this->addStoryFields($sub);

            $hexNet->categories()->sync([$sub->id]);
            $hexNet->touch();

            return $sub;
        });
    }

    /**
     * The fields of the follow-up form „Erzähl mehr“, e.g. photos of the garden. Fields that exist already are kept.
     */
    private function addStoryFields(MapPointCategory $category): void
    {
        $formDefinition = $category->findOrCreateFormDefinition();
        $existing = $formDefinition->fields()->pluck('label')->all();
        $sortOrder = $formDefinition->fields()->max('sort_order') ?? -1;
        $publicFieldIds = $category->publicFields()->pluck('id')->all();

        foreach (HexNetDefaults::STORY_FIELDS as $story) {
            if (in_array($story['label'], $existing, true)) {
                continue;
            }

            $field = $formDefinition->fields()->create([
                'type' => FieldType::from($story['type']),
                'label' => $story['label'],
                'help_text' => $story['help_text'] ?? null,
                'max_images' => 1,
                'required' => false,
                'sort_order' => ++$sortOrder,
            ]);

            if (isset($story['option'])) {
                $field->options()->create(['label' => $story['option'], 'value' => 'ja', 'sort_order' => 0]);
            }

            if ($story['public']) {
                $publicFieldIds[] = $field->id;
            }
        }

        $category->syncPublicFields($publicFieldIds);
    }

    /**
     * Copies a placeholder icon to the public disk, where uploaded icons of measures live too. Each measure gets its own
     * copy, because the core deletes the file when a measure or its icon is replaced.
     */
    private function storeIcon(?string $icon): ?string
    {
        if ($icon === null || ! in_array($icon, HexNetDefaults::ICONS, true)) {
            return null;
        }

        $path = 'characteristics/hexnet-'.$icon.'-'.Str::uuid().'.svg';
        Storage::disk('public')->put($path, (string) file_get_contents(public_path('hexnet/icons/'.$icon.'.svg')));

        return $path;
    }

    private function addPhotoField(MapPointCharacteristic $characteristic): void
    {
        $field = $characteristic->findOrCreateFormDefinition()->fields()->create([
            'type' => FieldType::IMAGE,
            'label' => 'Foto',
            'help_text' => 'Optional: ein Foto dieser Maßnahme.',
            'max_images' => 1,
            'required' => false,
            'sort_order' => 0,
        ]);

        $characteristic->syncPublicFields([$field->id]);
    }

    /**
     * The join form based on the map point template: measures are required, email confirmation is on and an extra
     * question asks for a consultation. The story fields are left to the linked follow-up form „Erzähl mehr“.
     */
    public function createForm(HexNet $hexNet): FormDefinition
    {
        $category = $hexNet->categories()->orderByDesc('map_point_categories.id')->first()
            ?? throw new RuntimeException('The HexaNetz has no category yet.');

        return DB::transaction(function () use ($hexNet, $category): FormDefinition {
            $hexNet->loadMissing('group');
            $storyFieldUuids = FormField::where('form_definition_id', $category->form_definition_id)
                ->whereIn('label', array_column(HexNetDefaults::STORY_FIELDS, 'label'))
                ->pluck('uuid')
                ->all();
            $form = $this->formDefinitionService->createFromTemplate('map_point', $hexNet->group->uuid, $category, withFollowUp: true, followUpOnlyFieldUuids: $storyFieldUuids);
            $form->mapPointCreator?->followUpFormDefinition?->update(['name' => 'Erzähl mehr – '.$hexNet->name]);

            $form->update([
                'name' => 'Trittstein anmelden – '.$hexNet->name,
                'description' => $hexNet->cta_text ?? HexNetDefaults::CTA_TEXT,
                'requires_email_confirmation' => true,
                'success_message' => 'Danke für deinen Trittstein!',
            ]);

            $creator = $form->mapPointCreator;
            $creator?->characteristicsField?->update([
                'required' => true,
                'help_text' => 'Wähle mindestens eine Maßnahme, die es in deinem Garten gibt.',
            ]);
            $creator?->contactEmailField?->update(['required' => true]);

            $form->fields()->create([
                'type' => FieldType::TEXT,
                'label' => 'Name',
                'help_text' => 'Optional. Wird nicht veröffentlicht.',
                'max_length' => 255,
                'required' => false,
                'sort_order' => ($form->fields()->max('sort_order') ?? 0) + 1,
            ]);

            $consultation = $form->fields()->create([
                'type' => FieldType::CHECKBOX,
                'label' => 'Stadtnaturberatung',
                'required' => false,
                'sort_order' => ($form->fields()->max('sort_order') ?? 0) + 1,
            ]);
            $consultation->options()->create(['label' => 'Ich möchte eine Stadtnaturberatung', 'value' => 'ja', 'sort_order' => 0]);

            $contact = $form->fields()->create([
                'type' => FieldType::CHECKBOX,
                'label' => 'Kontakt',
                'help_text' => 'Andere können dir dann über die Karte eine Nachricht schicken. Deine E-Mail-Adresse wird nicht angezeigt.',
                'required' => false,
                'sort_order' => ($form->fields()->max('sort_order') ?? 0) + 1,
            ]);
            $contact->options()->create(['label' => 'Andere dürfen mich über die Karte kontaktieren', 'value' => 'ja', 'sort_order' => 0]);
            $creator?->contactEnabledField()->associate($contact)->save();

            $hexNet->update(['form_definition_id' => $form->id]);

            return $form;
        });
    }
}
