<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConsentChoiceData;
use App\Data\MapPointConsentCharacteristicData;
use App\Data\MapPointConsentConfigData;
use App\Data\MapPointConsentData;
use App\Enums\PublicConsent;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormDefinitionToMapPointCharacteristic;
use App\Models\FormDefinitionToMapPointSubcategory;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Support\Collection;

/**
 * Builds the consent of a person to show their map point. The consent covers keys: the point itself (coordinate),
 * title, description, location and the public fields of the category and characteristics (field:<uuid>).
 */
class MapPointConsentService
{
    public const string DEFAULT_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. Ich kann meine Einwilligung jederzeit über den Link in der Bestätigungsmail widerrufen.';

    public const string COORDINATE = 'coordinate';

    public const string LOCATION = 'location';

    public const string TITLE = 'title';

    public const string DESCRIPTION = 'description';

    private const array BASE_LABELS = [
        self::TITLE => 'Titel',
        self::DESCRIPTION => 'Beschreibung',
    ];

    public function __construct(private readonly MapPointFieldService $fieldService) {}

    public static function fieldKey(string $fieldUuid): string
    {
        return 'field:'.$fieldUuid;
    }

    /**
     * The details offered in the second level, with their labels. The location is not offered, forms do not fill it.
     *
     * @param  array<int, int>  $characteristicIds
     * @return array<string, string>
     */
    public function offeredKeys(?int $categoryId, array $characteristicIds): array
    {
        $publicFieldIds = MapPointCategory::publicFieldIds();
        $keys = self::BASE_LABELS;

        foreach ($this->fieldService->effectiveFields($categoryId, $characteristicIds) as $field) {
            if (in_array($field->id, $publicFieldIds, true)) {
                $keys[self::fieldKey($field->uuid)] = $field->label;
            }
        }

        return $keys;
    }

    /**
     * @param  array<int, string>  $labels
     */
    public function render(string $template, string $initiative, array $labels): string
    {
        return strtr($template, ['{initiative}' => $initiative, '{felder}' => implode(', ', $labels)]);
    }

    /**
     * Nothing chosen refuses, the first level consents to the point only, the second level to all offered details.
     *
     * @param  array<string, string>  $offered
     */
    public function decide(ConsentChoiceData $choice, array $offered, string $text): MapPointConsentData
    {
        if (! $choice->show) {
            return new MapPointConsentData(PublicConsent::REFUSED);
        }

        return new MapPointConsentData(
            consent: PublicConsent::GIVEN,
            text: $text,
            fields: $choice->details ? [self::COORDINATE, ...array_keys($offered)] : [self::COORDINATE],
        );
    }

    /**
     * The consent of a submission, built from the form as it is now. It is frozen in the target payload.
     *
     * @param  array<int, int>  $characteristicIds
     */
    public function forTarget(FormDefinitionToMapPoint $target, ConsentChoiceData $choice, ?int $categoryId, array $characteristicIds): MapPointConsentData
    {
        $offered = $this->offeredKeys($categoryId, $characteristicIds);

        return $this->decide($choice, $offered, $this->render($this->templateOf($target), $this->initiativeOf($target), array_values($offered)));
    }

    /**
     * A new consent given on the manage page, for the point's current category and characteristics.
     */
    public function forPoint(MapPoint $mapPoint, ConsentChoiceData $choice): MapPointConsentData
    {
        $mapPoint->loadMissing(['characteristics', 'group']);
        $target = $this->targetOf($mapPoint);
        $offered = $this->offeredKeys($mapPoint->category_id, $mapPoint->characteristics->modelKeys());
        $template = $target === null ? self::DEFAULT_TEXT : $this->templateOf($target);
        $initiative = $target === null ? $mapPoint->group->name : $this->initiativeOf($target);

        return $this->decide($choice, $offered, $this->render($template, $initiative, array_values($offered)));
    }

    /**
     * The text and labels the manage page shows for the point's current category and characteristics.
     *
     * @return array{text: string, labels: array<int, string>}
     */
    public function previewForPoint(MapPoint $mapPoint): array
    {
        $mapPoint->loadMissing(['characteristics', 'group']);
        $target = $this->targetOf($mapPoint);
        $labels = array_values($this->offeredKeys($mapPoint->category_id, $mapPoint->characteristics->modelKeys()));
        $template = $target === null ? self::DEFAULT_TEXT : $this->templateOf($target);
        $initiative = $target === null ? $mapPoint->group->name : $this->initiativeOf($target);

        return ['text' => $this->render($template, $initiative, $labels), 'labels' => $labels];
    }

    /**
     * Readable labels of consented keys, for admins. Keys of fields that were deleted since are shown as they are.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public function labelsOf(array $keys): array
    {
        $fieldLabels = FormField::whereIn('uuid', array_map(fn (string $key): string => substr($key, 6), array_filter($keys, fn (string $key): bool => str_starts_with($key, 'field:'))))
            ->pluck('label', 'uuid');

        return array_map(fn (string $key): string => match (true) {
            $key === self::COORDINATE => 'Standort',
            $key === self::LOCATION => 'Ortsbeschreibung',
            array_key_exists($key, self::BASE_LABELS) => self::BASE_LABELS[$key],
            str_starts_with($key, 'field:') => $fieldLabels->get(substr($key, 6), $key),
            default => $key,
        }, $keys);
    }

    public function frontendConfig(FormDefinitionToMapPoint $target): MapPointConsentConfigData
    {
        $target->loadMissing(['category', 'subcategoryField', 'subcategories', 'characteristicsField', 'characteristicMappings.characteristic']);
        $tree = MapPointCategory::tree();
        $publicFieldIds = MapPointCategory::publicFieldIds();
        $categoryId = $target->category?->id;

        $categoryIdsByOption = $categoryId === null ? new Collection : $target->subcategories
            ->filter(fn (FormDefinitionToMapPointSubcategory $subcategory): bool => in_array((int) $subcategory->map_point_category_id, $tree->descendantIds($categoryId), true))
            ->mapWithKeys(fn (FormDefinitionToMapPointSubcategory $subcategory): array => [(string) $subcategory->option_value => (int) $subcategory->map_point_category_id]);

        $characteristics = $target->characteristicMappings
            ->filter(fn (FormDefinitionToMapPointCharacteristic $mapping): bool => $mapping->characteristic !== null)
            ->sortBy([fn (FormDefinitionToMapPointCharacteristic $a, FormDefinitionToMapPointCharacteristic $b): int => [$a->characteristic->sort_order, $a->characteristic->id] <=> [$b->characteristic->sort_order, $b->characteristic->id]])
            ->map(fn (FormDefinitionToMapPointCharacteristic $mapping): MapPointConsentCharacteristicData => new MapPointConsentCharacteristicData(
                option_value: (string) $mapping->option_value,
                labels: $this->publicLabels($mapping->characteristic->form_definition_id, $publicFieldIds),
                selectable_for: $this->selectableFor($mapping->characteristic, $categoryId, $categoryIdsByOption->all(), $tree),
            ))
            ->values();

        return new MapPointConsentConfigData(
            text: strtr($this->templateOf($target), ['{initiative}' => $this->initiativeOf($target)]),
            base_labels: array_values(self::BASE_LABELS),
            subcategory_field_id: $target->subcategoryField?->uuid,
            default_category_labels: $this->categoryLabels($categoryId, $publicFieldIds, $tree),
            category_labels: $categoryIdsByOption->map(fn (int $id): array => $this->categoryLabels($id, $publicFieldIds, $tree))->all(),
            characteristics_field_id: $target->characteristicsField?->uuid,
            characteristics: $characteristics->toBase(),
        );
    }

    private function templateOf(FormDefinitionToMapPoint $target): string
    {
        return $target->consent_text ?: self::DEFAULT_TEXT;
    }

    private function initiativeOf(FormDefinitionToMapPoint $target): string
    {
        return $target->formDefinition->group->name;
    }

    private function targetOf(MapPoint $mapPoint): ?FormDefinitionToMapPoint
    {
        $submission = $mapPoint->pointable;

        return $submission instanceof FormSubmission ? $submission->formDefinition?->mapPointCreator : null;
    }

    /**
     * @param  array<int, int>  $publicFieldIds
     * @return array<int, string>
     */
    private function categoryLabels(?int $categoryId, array $publicFieldIds, MapPointCategoryTree $tree): array
    {
        return $this->publicLabels($categoryId === null ? null : $tree->fieldsFormDefinitionId($categoryId), $publicFieldIds);
    }

    /**
     * @param  array<int, int>  $publicFieldIds
     * @return array<int, string>
     */
    private function publicLabels(?int $formDefinitionId, array $publicFieldIds): array
    {
        if ($formDefinitionId === null) {
            return [];
        }

        return FormField::where('form_definition_id', $formDefinitionId)
            ->whereIn('id', $publicFieldIds)
            ->orderBy('sort_order')
            ->pluck('label')
            ->all();
    }

    /**
     * @param  array<string, int>  $categoryIdsByOption
     * @return array<int, string>
     */
    private function selectableFor(MapPointCharacteristic $characteristic, ?int $categoryId, array $categoryIdsByOption, MapPointCategoryTree $tree): array
    {
        $options = array_keys(array_filter($categoryIdsByOption, fn (int $id): bool => $characteristic->isSelectableFor($id, $tree)));
        $options = array_map(strval(...), $options);

        return $characteristic->isSelectableFor($categoryId, $tree) ? ['', ...$options] : $options;
    }
}
