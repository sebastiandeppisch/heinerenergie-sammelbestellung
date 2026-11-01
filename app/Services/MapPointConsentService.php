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
    /** The text of the first level, the point with its location only. It must not list fields. */
    public const string DEFAULT_POINT_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} mit seinem Standort öffentlich angezeigt wird. {widerruf}';

    /** The text of the second level, {felder} lists the details from the form. */
    public const string DEFAULT_DETAILS_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. {widerruf}';

    /** {widerruf} when the form asks for the contact address, which gets the manage link. */
    public const string REVOCATION_BY_MAIL = 'Ich kann meine Einwilligung jederzeit über den Link widerrufen, den ihr mir per E-Mail schickt.';

    /** {widerruf} without a contact address. The stored text adds the address of the privacy policy. */
    public const string REVOCATION_BY_POLICY = 'Ich kann meine Einwilligung jederzeit widerrufen. Wie, steht in der Datenschutzerklärung.';

    public const string COORDINATE = 'coordinate';

    public const string LOCATION = 'location';

    public const string TITLE = 'title';

    public const string DESCRIPTION = 'description';

    private const array BASE_LABELS = [
        self::TITLE => 'Titel',
        self::DESCRIPTION => 'Beschreibung',
    ];

    private const string LOCATION_LABEL = 'Ortsbeschreibung';

    public function __construct(private readonly MapPointFieldService $fieldService) {}

    public static function fieldKey(string $fieldUuid): string
    {
        return 'field:'.$fieldUuid;
    }

    /**
     * The details offered in the second level, with their labels: title, description, the location, then the public
     * fields of the category and characteristics. Public fields are offered even without a value, they may be filled in
     * later by the person. The location only with a value, an admin adding one later must not make it public.
     *
     * @param  array<int, int>  $characteristicIds
     * @return array<string, string>
     */
    public function offeredKeys(?int $categoryId, array $characteristicIds, bool $withLocation): array
    {
        $publicFieldIds = MapPointCategory::publicFieldIds();
        $keys = self::BASE_LABELS;

        if ($withLocation) {
            $keys[self::LOCATION] = self::LOCATION_LABEL;
        }

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
    public function render(string $template, string $initiative, array $labels, string $revocation): string
    {
        return strtr($template, ['{initiative}' => $initiative, '{felder}' => implode(', ', $labels), '{widerruf}' => $revocation]);
    }

    /**
     * The revocation sentence as it is stored, with the address of the privacy policy when there is no manage link.
     */
    public function revocation(bool $byMail): string
    {
        return $byMail ? self::REVOCATION_BY_MAIL : self::REVOCATION_BY_POLICY.' ('.route('datapolicy').')';
    }

    /**
     * Nothing chosen refuses. Each level stores its own text and exactly its keys: the first level the point only,
     * the second level the point and all offered details.
     *
     * @param  array<string, string>  $offered
     */
    public function decide(ConsentChoiceData $choice, array $offered, string $pointText, string $detailsText): MapPointConsentData
    {
        if (! $choice->show) {
            return new MapPointConsentData(PublicConsent::REFUSED);
        }

        return $choice->details
            ? new MapPointConsentData(PublicConsent::GIVEN, $detailsText, [self::COORDINATE, ...array_keys($offered)])
            : new MapPointConsentData(PublicConsent::GIVEN, $pointText, [self::COORDINATE]);
    }

    /**
     * The consent of a submission, built from the form as it is now. It is frozen in the target payload.
     *
     * @param  array<int, int>  $characteristicIds
     */
    public function forTarget(FormDefinitionToMapPoint $target, ConsentChoiceData $choice, ?int $categoryId, array $characteristicIds, bool $hasLocation): MapPointConsentData
    {
        $offered = $this->offeredKeys($categoryId, $characteristicIds, $target->location_field_id !== null && $hasLocation);
        [$pointText, $detailsText] = $this->texts($target, $this->initiativeOf($target), array_values($offered), $this->revocation($target->contact_email_field_id !== null));

        return $this->decide($choice, $offered, $pointText, $detailsText);
    }

    /**
     * A new consent given on the manage page, for the point's current category, characteristics and location. The person
     * came by the manage link, so the revocation names it.
     */
    public function forPoint(MapPoint $mapPoint, ConsentChoiceData $choice): MapPointConsentData
    {
        $offered = $this->offeredForPoint($mapPoint);
        [$pointText, $detailsText] = $this->textsForPoint($mapPoint, array_values($offered), $this->revocation($mapPoint->contact_email !== null));

        return $this->decide($choice, $offered, $pointText, $detailsText);
    }

    /**
     * The texts and labels the manage page shows for the point as it is now.
     *
     * @return array{point_text: string, details_text: string, labels: array<int, string>}
     */
    public function previewForPoint(MapPoint $mapPoint): array
    {
        $labels = array_values($this->offeredForPoint($mapPoint));
        [$pointText, $detailsText] = $this->textsForPoint($mapPoint, $labels, $this->revocation($mapPoint->contact_email !== null));

        return ['point_text' => $pointText, 'details_text' => $detailsText, 'labels' => $labels];
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
            $key === self::LOCATION => self::LOCATION_LABEL,
            array_key_exists($key, self::BASE_LABELS) => self::BASE_LABELS[$key],
            str_starts_with($key, 'field:') => $fieldLabels->get(substr($key, 6), $key),
            default => $key,
        }, $keys);
    }

    public function frontendConfig(FormDefinitionToMapPoint $target): MapPointConsentConfigData
    {
        $target->loadMissing(['category', 'subcategoryField', 'subcategories', 'characteristicsField', 'characteristicMappings.characteristic', 'locationField']);
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

        $byMail = $target->contact_email_field_id !== null;

        return new MapPointConsentConfigData(
            point_text: strtr($this->pointTemplateOf($target), ['{initiative}' => $this->initiativeOf($target)]),
            details_text: strtr($this->detailsTemplateOf($target), ['{initiative}' => $this->initiativeOf($target)]),
            revocation_text: $byMail ? self::REVOCATION_BY_MAIL : self::REVOCATION_BY_POLICY,
            revocation_url: $byMail ? null : route('datapolicy'),
            base_labels: array_values(self::BASE_LABELS),
            location_field_id: $target->locationField?->uuid,
            location_label: self::LOCATION_LABEL,
            subcategory_field_id: $target->subcategoryField?->uuid,
            default_category_labels: $this->categoryLabels($categoryId, $publicFieldIds, $tree),
            category_labels: $categoryIdsByOption->map(fn (int $id): array => $this->categoryLabels($id, $publicFieldIds, $tree))->all(),
            characteristics_field_id: $target->characteristicsField?->uuid,
            characteristics: $characteristics->toBase(),
        );
    }

    private function pointTemplateOf(?FormDefinitionToMapPoint $target): string
    {
        return $target?->consent_point_text ?: self::DEFAULT_POINT_TEXT;
    }

    private function detailsTemplateOf(?FormDefinitionToMapPoint $target): string
    {
        return $target?->consent_text ?: self::DEFAULT_DETAILS_TEXT;
    }

    /**
     * @param  array<int, string>  $labels
     * @return array{0: string, 1: string}
     */
    private function texts(?FormDefinitionToMapPoint $target, string $initiative, array $labels, string $revocation): array
    {
        return [
            $this->render($this->pointTemplateOf($target), $initiative, [], $revocation),
            $this->render($this->detailsTemplateOf($target), $initiative, $labels, $revocation),
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @return array{0: string, 1: string}
     */
    private function textsForPoint(MapPoint $mapPoint, array $labels, string $revocation): array
    {
        $mapPoint->loadMissing('group');
        $target = $this->targetOf($mapPoint);

        return $this->texts($target, $target === null ? $mapPoint->group->name : $this->initiativeOf($target), $labels, $revocation);
    }

    /**
     * @return array<string, string>
     */
    private function offeredForPoint(MapPoint $mapPoint): array
    {
        $mapPoint->loadMissing('characteristics');

        return $this->offeredKeys($mapPoint->category_id, $mapPoint->characteristics->modelKeys(), filled($mapPoint->location));
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
