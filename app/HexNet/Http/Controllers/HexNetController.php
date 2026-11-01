<?php

declare(strict_types=1);

namespace App\HexNet\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\HexNet\Data\HexNetData;
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
use App\ValueObjects\Coordinate;
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
            'form' => $hexNet->update(['form_definition_id' => $this->validateForm($request)]),
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
     * Cells of a polygon for the preview in the wizard, without saving.
     */
    public function gridPreview(Request $request): JsonResponse
    {
        $this->authorize('create', HexNet::class);

        $data = $request->validate([
            'play_area.coordinates' => ['required', 'array', 'min:3', 'max:2000'],
            'play_area.coordinates.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'play_area.coordinates.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'origin.lat' => ['nullable', 'numeric'],
            'origin.lng' => ['nullable', 'numeric'],
            'hex_radius_m' => ['required', 'integer', 'min:'.HexNet::MIN_RADIUS_M, 'max:'.HexNet::MAX_RADIUS_M],
            'hex_net_id' => ['nullable', 'uuid'],
        ]);

        $polygon = new Polygon($data['play_area']['coordinates']);
        $origin = isset($data['origin']['lat'], $data['origin']['lng']) ? Coordinate::fromArray($data['origin']) : $polygon->getCenter();
        $grid = new HexGrid($origin, (float) $data['hex_radius_m']);
        $cells = $grid->cellsInPolygon($polygon);

        if (count($cells) > 20000) {
            return response()->json(['message' => 'Zu viele Waben. Wähle einen größeren Radius oder ein kleineres Spielfeld.'], 422);
        }

        $hexNet = isset($data['hex_net_id']) ? HexNet::where('uuid', $data['hex_net_id'])->first() : null;
        $counts = $hexNet !== null && $request->user()->can('update', $hexNet) ? $this->gridService->countIn($hexNet, $grid, $polygon, $cells) : [];

        return response()->json([
            'cells' => HexNetGridService::toGeoJson($grid, $cells),
            'counts' => (object) $counts,
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

        return [...$data, 'group_id' => $group->id];
    }

    private function updateCategories(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate([
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['uuid', 'exists:map_point_categories,uuid'],
        ]);

        $ids = MapPointCategory::whereIn('uuid', $data['category_ids'])->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
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

        $hexNet->save();
    }

    private function updateGrid(Request $request, HexNet $hexNet): void
    {
        $data = $request->validate([
            'hex_radius_m' => ['required', 'integer', 'min:'.HexNet::MIN_RADIUS_M, 'max:'.HexNet::MAX_RADIUS_M],
            'grid_locked' => ['required', 'boolean'],
            'threshold_medium' => ['required', 'integer', 'min:1', 'max:50'],
            'threshold_dark' => ['required', 'integer', 'gt:threshold_medium', 'max:100'],
            'jitter_m' => ['required', 'integer', 'min:0', 'max:200'],
            'confirm_radius_change' => ['sometimes', 'boolean'],
        ]);

        $radiusChanged = (int) $data['hex_radius_m'] !== $hexNet->hex_radius_m;

        if ($radiusChanged && $hexNet->grid_locked && $data['grid_locked']) {
            throw ValidationException::withMessages(['hex_radius_m' => 'Das Raster ist gesperrt. Entsperre es, um den Radius zu ändern.']);
        }

        if ($radiusChanged && $hexNet->isActive() && ! ($data['confirm_radius_change'] ?? false)) {
            throw ValidationException::withMessages(['confirm_radius_change' => 'Das HexaNetz läuft bereits. Bestätige die Änderung des Radius.']);
        }

        unset($data['confirm_radius_change']);
        $hexNet->update($data);
    }

    private function validateForm(Request $request): ?int
    {
        $data = $request->validate(['form_definition_id' => ['nullable', 'uuid', 'exists:form_definitions,uuid']]);

        return $data['form_definition_id'] === null ? null : FormDefinition::where('uuid', $data['form_definition_id'])->value('id');
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
        return [
            'Kategorien' => $hexNet->categories()->exists(),
            'Spielfeld' => $hexNet->hasPlayArea(),
            'Mitmach-Formular' => $hexNet->form_definition_id !== null,
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

        $forms = $hexNet === null ? collect() : FormDefinition::query()
            ->whereIn('group_id', $hexNet->group->getHierarchyIds())
            ->whereHas('mapPointCreator')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name']);

        return [
            'hexNet' => $hexNet === null ? null : HexNetData::fromModel($hexNet, $this->gridService->stats($hexNet)),
            'checklist' => $hexNet === null ? [] : $this->checklist($hexNet),
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
                'min_radius' => HexNet::MIN_RADIUS_M,
                'max_radius' => HexNet::MAX_RADIUS_M,
            ],
            'steps' => self::STEPS,
        ];
    }
}
