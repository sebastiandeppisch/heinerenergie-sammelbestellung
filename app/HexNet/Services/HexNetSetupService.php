<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\Enums\FieldType;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\Models\FormDefinition;
use App\Models\MapPointCategory;
use App\Services\FormDefinitionService;
use Illuminate\Support\Facades\DB;
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
     * @param  array<int, array{name: string, color: string}>  $measures
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
                    $main->characteristics()->create(['name' => $measure['name'], 'color' => $measure['color'], 'sort_order' => ++$sortOrder]);
                }
            }

            $sub = MapPointCategory::firstOrCreate(
                ['group_id' => $hexNet->group_id, 'parent_id' => $main->id, 'name' => $subName],
            );

            $hexNet->categories()->sync([$sub->id]);
            $hexNet->touch();

            return $sub;
        });
    }

    /**
     * The join form based on the map point template: measures are required, email confirmation is on and an extra
     * question asks for a consultation.
     */
    public function createForm(HexNet $hexNet): FormDefinition
    {
        $category = $hexNet->categories()->orderByDesc('map_point_categories.id')->first()
            ?? throw new RuntimeException('The HexaNetz has no category yet.');

        return DB::transaction(function () use ($hexNet, $category): FormDefinition {
            $hexNet->loadMissing('group');
            $form = $this->formDefinitionService->createFromTemplate('map_point', $hexNet->group->uuid, $category);

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

            $consultation = $form->fields()->create([
                'type' => FieldType::CHECKBOX,
                'label' => 'Stadtnaturberatung',
                'required' => false,
                'sort_order' => ($form->fields()->max('sort_order') ?? 0) + 1,
            ]);
            $consultation->options()->create(['label' => 'Ich möchte eine Stadtnaturberatung', 'value' => 'ja', 'sort_order' => 0]);

            $hexNet->update(['form_definition_id' => $form->id]);

            return $form;
        });
    }
}
