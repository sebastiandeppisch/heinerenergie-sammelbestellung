<?php

declare(strict_types=1);

namespace App\HexNet\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\HexNet\Data\HexNetData;
use App\HexNet\Enums\HexNetScoringMode;
use App\HexNet\Enums\HexNetStatus;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexGrid;
use App\HexNet\Services\HexNetCategoryValidator;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetSetupService;
use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Services\MapPointVisibilityService;
use App\ValueObjects\Polygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HexNetController extends Controller
{
    public const array STEPS = ['basics', 'texts', 'categories', 'play-area', 'grid', 'form', 'launch'];

    private const string TOO_MANY_CELLS = 'Zu viele Waben. Wähle einen größeren Radius oder ein kleineres Spielfeld.';

    public function __construct(private readonly HexNetGridService $gridService) {}

    public function index(): Response
    {
        $this->authorize('viewAny', HexNet::class);

        $hexNets = $this->visibleQuery()
            ->with('group', 'categories', 'formDefinition')
            ->latest()
            ->get()
            ->map(fn (HexNet $hexNet): HexNetData => HexNetData::fromModel($hexNet, $this->gridService->stats($hexNet)));

        return Inertia::render('HexNet/Index', ['hexNets' => $hexNets]);
    }

    public function create(): Response
    {
        $this->authorize('create', HexNet::class);

        return Inertia::render('HexNet/Wizard', [...$this->wizardProps(null), 'step' => 'basics']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HexNet::class);

        $data = $this->validateBasics($request, null);

        $hexNet = HexNet::create([
            ...$data,
            'title' => HexNetDefaults::TITLE,
            'subtitle' => HexNetDefaults::SUBTITLE,
            'cta_text' => HexNetDefaults::CTA_TEXT,
            'hex_radius_m' => HexNetDefaults::RADIUS_M,
            'threshold_medium' => HexNetDefaults::THRESHOLD_MEDIUM,
            'threshold_dark' => HexNetDefaults::THRESHOLD_DARK,
            'jitter_m' => HexNetDefaults::JITTER_M,
        ]);

        return redirect()->route('hexnets.edit', [$hexNet, 'step' => 'texts'])->with('success', 'Der Entwurf wurde angelegt');
    }

    public function edit(Request $request, HexNet $hexNet): Response
    {
        $this->authorize('update', $hexNet);

        $step = in_array($request->query('step'), self::STEPS, true) ? $request->query('step') : 'basics';

        return Inertia::render('HexNet/Wizard', [...$this->wizardProps($hexNet), 'step' => $step]);
    }

    public function updateStep(Request $request, HexNet $hexNet, string $step): RedirectResponse
    {
        $this->authorize('update', $hexNet);

        abort_unless(in_array($step, self::STEPS, true), 404);

        match ($step) {
            'basics' => $hexNet->update($this->validateBasics($request, $hexNet)),
            'texts' => $hexNet->update($request->validate([
                'title' => ['required', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:255'],
                'cta_text' => ['nullable', 'string', 'max:2000'],
            ])),
            'categories' => $this->updateCategories($request, $hexNet),
            'play-area' => $this->updatePlayArea($request, $hexNet),
            'grid' => $this->updateGrid($request, $hexNet),
            'form' => $hexNet->update([
                'form_definition_id' => $this->validateForm($request, $hexNet),
                'auto_publish' => $request->boolean('auto_publish'),
            ]),
            'launch' => $this->updateStatus($request, $hexNet),
        };

        $next = $request->input('next');

        return in_array($next, self::STEPS, true)
            ? redirect()->route('hexnets.edit', [$hexNet, 'step' => $next])
            : redirect()->back()->with('success', 'Gespeichert');
    }

    public function setupCategories(Request $request, HexNet $hexNet, HexNetSetupService $setup): RedirectResponse
    {
        $this->authorize('update', $hexNet);

        $data = $request->validate([
            'main_name' => ['required', 'string', 'max:255'],
            'sub_name' => ['required', 'string', 'max:255'],
            'measures' => ['required', 'array', 'min:1'],
            'measures.*.name' => ['required', 'string', 'max:255'],
            'measures.*.color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'measures.*.icon' => ['nullable', Rule::in(HexNetDefaults::ICONS)],
        ]);

        $setup->createCategories($hexNet, $data['main_name'], $data['sub_name'], $data['measures']);

        return redirect()->back()->with('success', 'Kategorien und Maßnahmen wurden angelegt');
    }

    public function setupForm(HexNet $hexNet, HexNetSetupService $setup): RedirectResponse
    {
        $this->authorize('update', $hexNet);

        if ($hexNet->categories()->doesntExist()) {
            throw ValidationException::withMessages(['form_definition_id' => 'Lege zuerst die Kategorien an.']);
        }

        $setup->createForm($hexNet);

        return redirect()->back()->with('success', 'Das Mitmach-Formular wurde erzeugt');
    }

    public function destroy(HexNet $hexNet): RedirectResponse
    {
        $this->authorize('delete', $hexNet);

        $hexNet->delete();

        return redirect()->route('hexnets.index')->with('info', 'Das HexaNetz '.e($hexNet->name).' wurde gelöscht');
    }

    /**
     * Cells and counts of the saved play area for another radius, for the preview in the wizard. Origin and play area
     * are always the saved ones, so the preview cannot be used to probe the counts of arbitrary areas.
     */
    public function gridPreview(Request $request, HexNet $hexNet): JsonResponse
    {
        $this->authorize('update', $hexNet);

        $data = $request->validate([
            'hex_radius_m' => ['required', 'integer', 'multiple_of:10', 'min:'.HexNet::MIN_RADIUS_M, 'max:'.HexNet::MAX_RADIUS_M],
            'scoring_mode' => ['sometimes', Rule::enum(HexNetScoringMode::class)],
        ]);

        if (isset($data['scoring_mode'])) {
            $hexNet->scoring_mode = HexNetScoringMode::from($data['scoring_mode']);
        }

        $origin = $hexNet->origin();
        $playArea = $hexNet->play_area;

        if ($origin === null || $playArea === null || ! $hexNet->hasPlayArea()) {
            return response()->json(['message' => 'Zeichne zuerst das Spielfeld.'], 422);
        }

        $grid = new HexGrid($origin, (float) $data['hex_radius_m']);

        if (HexNetGridService::estimateCells($grid, $playArea) > HexNetGridService::MAX_CELLS) {
            return response()->json(['message' => self::TOO_MANY_CELLS], 422);
        }

        $cells = $grid->cellsInPolygon($playArea);

        return response()->json([
            'cells' => HexNetGridService::toGeoJson($grid, $cells),
            'counts' => (object) $this->gridService->countIn($hexNet, $grid, $playArea, $cells),
            'count' => count($cells),
            'cell_area_ha' => round($grid->cellAreaHa(), 2),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBasics(Request $request, ?HexNet $hexNet): array
    {
        if (! $request->filled('slug')) {
            $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('hex_nets', 'slug')->ignore($hexNet?->id)],
            'group_id' => ['required', 'uuid', 'exists:groups,uuid'],
        ]);

        $group = Group::where('uuid', $data['group_id'])->firstOrFail();

        if (! $this->visibility()->isSelectableGroup($request->user(), $group)) {
            throw ValidationException::withMessages(['group_id' => 'Du darfst dieser Initiative kein HexaNetz zuordnen.']);
        }

        if ($hexNet !== null && $hexNet->group_id !== $group->id) {
            $foreignCategories = $hexNet->categories()->whereNotIn('map_point_categories.group_id', $group->getHierarchyIds())->exists();
            $foreignForm = $hexNet->formDefinition !== null && ! in_array($hexNet->formDefinition->group_id, $group->getHierarchyIds(), true);

            if ($foreignCategories || $foreignForm) {
                throw ValidationException::withMessages(['group_id' => 'Kategorien oder Formular gehören nicht zu dieser Initiative. Wähle sie zuerst neu.']);
            }
        }

        return [...$data, 'group_id' => $group->id];
    }

    private function updateCategories(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate([
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['uuid', 'exists:map_point_categories,uuid'],
        ]);

        $ids = MapPointCategory::query()
            ->whereIn('group_id', $hexNet->group->getHierarchyIds())
            ->whereIn('uuid', $data['category_ids'])
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if (count($ids) !== count(array_unique($data['category_ids']))) {
            throw ValidationException::withMessages(['category_ids' => 'Wähle nur Kategorien, die diese Initiative nutzen darf.']);
        }

        $error = app(HexNetCategoryValidator::class)->validate($ids);

        if ($error !== null) {
            throw ValidationException::withMessages(['category_ids' => $error]);
        }

        $hexNet->categories()->sync($ids);
        $hexNet->touch();
    }

    private function updatePlayArea(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate([
            'play_area.coordinates' => ['required', 'array', 'min:3', 'max:2000'],
            'play_area.coordinates.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'play_area.coordinates.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $polygon = new Polygon($data['play_area']['coordinates']);
        $hexNet->play_area = $polygon;

        if ($hexNet->origin() === null) {
            $center = $polygon->getCenter();
            $hexNet->grid_origin_lat = $center->lat;
            $hexNet->grid_origin_lng = $center->lng;
        }

        $this->assertCellLimit($hexNet, $hexNet->hex_radius_m, 'play_area.coordinates');
        $hexNet->save();
    }

    private function updateGrid(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate([
            'hex_radius_m' => ['required', 'integer', 'multiple_of:10', 'min:'.HexNet::MIN_RADIUS_M, 'max:'.HexNet::MAX_RADIUS_M],
            'grid_locked' => ['required', 'boolean'],
            'threshold_medium' => ['required', 'integer', 'min:1', 'max:50'],
            'threshold_dark' => ['required', 'integer', 'gt:threshold_medium', 'max:100'],
            'scoring_mode' => ['sometimes', Rule::enum(HexNetScoringMode::class)],
            'jitter_m' => ['required', 'integer', 'multiple_of:10', 'min:'.HexNet::MIN_JITTER_M, 'max:'.HexNet::MAX_JITTER_M],
            'color_open' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_light' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_medium' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_dark' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'confirm_radius_change' => ['sometimes', 'boolean'],
            'confirm_scoring_mode_change' => ['sometimes', 'boolean'],
        ]);

        $radiusChanged = (int) $data['hex_radius_m'] !== $hexNet->hex_radius_m;
        $jitterChanged = (int) $data['jitter_m'] !== $hexNet->jitter_m;

        if ($hexNet->grid_locked && ($radiusChanged || $jitterChanged)) {
            throw ValidationException::withMessages([$radiusChanged ? 'hex_radius_m' : 'jitter_m' => 'Das Raster ist gesperrt. Entsperre es und speichere, bevor du Radius oder Versatz änderst.']);
        }

        if ($radiusChanged) {
            $this->assertCellLimit($hexNet, (int) $data['hex_radius_m'], 'hex_radius_m');
        }

        if ($radiusChanged && $hexNet->isActive() && ! ($data['confirm_radius_change'] ?? false)) {
            throw ValidationException::withMessages(['confirm_radius_change' => 'Das HexaNetz läuft bereits. Bestätige die Änderung des Radius.']);
        }

        if (($data['scoring_mode'] ?? $hexNet->scoring_mode->value) !== $hexNet->scoring_mode->value && $hexNet->isActive() && ! ($data['confirm_scoring_mode_change'] ?? false)) {
            throw ValidationException::withMessages(['confirm_scoring_mode_change' => 'Das HexaNetz läuft bereits. Bestätige die Änderung der Zählweise, die Waben färben sich danach neu.']);
        }

        unset($data['confirm_radius_change'], $data['confirm_scoring_mode_change']);
        $hexNet->update($data);
    }

    /**
     * Only map point forms of the group hierarchy that create points in one of the categories.
     */
    private function validateForm(Request $request, HexNet $hexNet): ?int
    {
        $data = $request->validate(['form_definition_id' => ['nullable', 'uuid']]);

        if ($data['form_definition_id'] === null) {
            return null;
        }

        $id = $this->usableForms($hexNet)->where('uuid', $data['form_definition_id'])->value('id');

        if ($id === null) {
            throw ValidationException::withMessages(['form_definition_id' => 'Das Formular muss Kartenpunkte in einer Kategorie dieses HexaNetzes anlegen.']);
        }

        return (int) $id;
    }

    /**
     * @return Builder<FormDefinition>
     */
    private function usableForms(HexNet $hexNet): Builder
    {
        $categoryIds = $this->gridService->categoryIds($hexNet);

        return FormDefinition::query()
            ->whereIn('group_id', $hexNet->group->getHierarchyIds())
            ->whereHas('mapPointCreator', fn (Builder $query) => $query->whereIn('map_point_category_id', $categoryIds));
    }

    private function assertCellLimit(HexNet $hexNet, int $radius, string $errorKey): void
    {
        $origin = $hexNet->origin();

        if ($origin !== null && $hexNet->play_area !== null && HexNetGridService::estimateCells(new HexGrid($origin, $radius), $hexNet->play_area) > HexNetGridService::MAX_CELLS) {
            throw ValidationException::withMessages([$errorKey => self::TOO_MANY_CELLS]);
        }
    }

    private function updateStatus(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate(['status' => ['required', Rule::enum(HexNetStatus::class)]]);
        $status = HexNetStatus::from($data['status']);

        if ($status === HexNetStatus::ACTIVE) {
            $missing = array_keys(array_filter($this->checklist($hexNet), fn (bool $done): bool => ! $done));

            if ($missing !== []) {
                throw ValidationException::withMessages(['status' => 'Zum Starten fehlt noch: '.implode(', ', $missing).'.']);
            }
        }

        $hexNet->update(['status' => $status]);
    }

    /**
     * @return array<string, bool>
     */
    private function checklist(HexNet $hexNet): array
    {
        $form = $hexNet->formDefinition()->with('mapPointCreator.characteristicsField')->first();

        return [
            'Kategorien' => $hexNet->categories()->exists(),
            'Spielfeld' => $hexNet->hasPlayArea(),
            'Mitmach-Formular' => $form !== null,
            'Formular mit E-Mail-Bestätigung' => (bool) $form?->requires_email_confirmation,
            'Maßnahmen im Formular Pflicht' => (bool) $form?->mapPointCreator?->characteristicsField?->required,
        ];
    }

    /**
     * @return Builder<HexNet>
     */
    private function visibleQuery(): Builder
    {
        $group = app(GroupContextContract::class)->getCurrentGroup();

        if ($group === null) {
            return HexNet::query();
        }

        return HexNet::query()->whereIn('group_id', [...$group->getHierarchyIds(), ...$group->getSubtreeIds()]);
    }

    /**
     * Resolved per call, because the group context is bound by a middleware after the controller is built.
     */
    private function visibility(): MapPointVisibilityService
    {
        return app(MapPointVisibilityService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function wizardProps(?HexNet $hexNet): array
    {
        $hexNet?->load('group', 'categories', 'formDefinition');
        $tree = MapPointCategory::tree();
        $groupIds = $hexNet?->group->getHierarchyIds();

        $categories = ($groupIds === null ? $this->visibility()->usableCategories() : MapPointCategory::query()->whereIn('group_id', $groupIds))
            ->with('group')
            ->withCount('characteristics')
            ->orderBy('name')
            ->get();

        $forms = $hexNet === null ? collect() : $this->usableForms($hexNet)->orderBy('name')->get(['id', 'uuid', 'name']);

        return [
            'hexNet' => $hexNet === null ? null : HexNetData::fromModel($hexNet, $this->gridService->stats($hexNet)),
            'checklist' => $hexNet === null ? [] : $this->checklist($hexNet),
            'measureCount' => $hexNet === null ? 0 : $this->gridService->measures($hexNet)->count(),
            'groups' => $this->visibility()->selectableGroups()->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group))->values(),
            'categories' => $categories->map(function (MapPointCategory $category) use ($tree): MapPointCategoryData {
                $data = MapPointCategoryData::fromModel($category, tree: $tree);
                $data->characteristics_count = (int) $category->characteristics_count;

                return $data;
            })->values(),
            'forms' => $forms->map(fn (FormDefinition $form): array => ['id' => $form->uuid, 'name' => $form->name])->values(),
            'defaults' => [
                'main_category' => HexNetDefaults::MAIN_CATEGORY,
                'sub_category' => HexNetDefaults::MAIN_CATEGORY.' '.now()->addYear()->year,
                'measures' => HexNetDefaults::MEASURES,
                'icons' => array_map(fn (string $icon): array => ['key' => $icon, 'url' => HexNetDefaults::iconUrl($icon)], HexNetDefaults::ICONS),
                'min_radius' => HexNet::MIN_RADIUS_M,
                'max_radius' => HexNet::MAX_RADIUS_M,
            ],
            'steps' => self::STEPS,
        ];
    }
}
