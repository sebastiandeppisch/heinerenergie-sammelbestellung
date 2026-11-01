<?php

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetCategoryValidator;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetResolver;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\FormDefinitionService;
use App\Services\SessionService;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
    $this->parent = Group::factory()->create();
    $this->child = Group::factory()->create(['parent_id' => $this->parent->id]);
    $this->sibling = Group::factory()->create(['parent_id' => $this->parent->id]);
    $this->hexNet = HexNet::factory()->withPlayArea()->create(['group_id' => $this->child->id]);
});

function hexNetGroupAdmin(Group $group): User
{
    $user = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($user, ['is_admin' => true]);
    test()->actingAs($user);
    app(SessionService::class)->actAsGroup($group, true);

    return $user;
}

test('subgroups see the HexaNetz', function (): void {
    $grandchild = Group::factory()->create(['parent_id' => $this->child->id]);

    hexNetGroupAdmin($grandchild);
    $this->get(route('hexnets.index'))->assertInertia(fn ($page) => $page->component('HexNet/Index')->has('hexNets', 1));
});

test('neighbouring groups do not see the HexaNetz', function (): void {
    hexNetGroupAdmin($this->sibling);

    $this->get(route('hexnets.index'))->assertInertia(fn ($page) => $page->has('hexNets', 0));
    $this->get(route('hexnets.edit', $this->hexNet))->assertForbidden();
});

test('subgroups cannot edit the HexaNetz of their parent', function (): void {
    $grandchild = Group::factory()->create(['parent_id' => $this->child->id]);
    hexNetGroupAdmin($grandchild);

    $this->get(route('hexnets.edit', $this->hexNet))->assertForbidden();
});

test('the wizard creates categories, form and starts the HexaNetz', function (): void {
    hexNetGroupAdmin($this->child);

    $this->post(route('hexnets.store'), ['name' => 'Grünes Netz', 'group_id' => $this->child->uuid])->assertRedirect();
    $hexNet = HexNet::where('slug', 'grunes-netz')->firstOrFail();

    $this->put(route('hexnets.update-step', [$hexNet, 'launch']), ['status' => 'active'])->assertSessionHasErrors('status');

    $this->post(route('hexnets.setup-categories', $hexNet), [
        'main_name' => 'Trittsteine',
        'sub_name' => 'Trittsteine 2027',
        'measures' => HexNetDefaults::MEASURES,
    ])->assertSessionHasNoErrors();
    $this->put(route('hexnets.update-step', [$hexNet, 'play-area']), ['play_area' => HexNet::factory()->withPlayArea()->raw()['play_area']])->assertSessionHasNoErrors();
    $this->post(route('hexnets.setup-form', $hexNet))->assertSessionHasNoErrors();
    $this->put(route('hexnets.update-step', [$hexNet, 'launch']), ['status' => 'active'])->assertSessionHasNoErrors();

    $hexNet->refresh();
    expect($hexNet->status)->toBe(HexNetStatus::ACTIVE)
        ->and($hexNet->origin())->not->toBeNull()
        ->and($hexNet->categories()->pluck('name')->all())->toBe(['Trittsteine 2027'])
        ->and($hexNet->formDefinition->requires_email_confirmation)->toBeTrue()
        ->and($hexNet->formDefinition->mapPointCreator->characteristicsField->required)->toBeTrue()
        ->and(MapPointCharacteristic::count())->toBe(count(HexNetDefaults::MEASURES));
});

test('the grid rules are validated', function (): void {
    hexNetGroupAdmin($this->child);
    $valid = ['hex_radius_m' => 150, 'grid_locked' => false, 'threshold_medium' => 2, 'threshold_dark' => 3, 'jitter_m' => 40];

    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), [...$valid, 'hex_radius_m' => 99])->assertSessionHasErrors('hex_radius_m');
    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), [...$valid, 'threshold_dark' => 2])->assertSessionHasErrors('threshold_dark');
    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), $valid)->assertSessionHasNoErrors();
});

test('a locked grid or an active HexaNetz protects the radius', function (): void {
    hexNetGroupAdmin($this->child);
    $this->hexNet->update(['grid_locked' => true]);
    $data = ['hex_radius_m' => 200, 'grid_locked' => true, 'threshold_medium' => 2, 'threshold_dark' => 3, 'jitter_m' => 40];

    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), $data)->assertSessionHasErrors('hex_radius_m');

    $this->hexNet->update(['grid_locked' => false, 'status' => HexNetStatus::ACTIVE]);
    $data['grid_locked'] = false;
    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), $data)->assertSessionHasErrors('confirm_radius_change');
    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), [...$data, 'confirm_radius_change' => true])->assertSessionHasNoErrors();

    expect($this->hexNet->fresh()->hex_radius_m)->toBe(200);
});

test('categories must come from one subtree with the same measures', function (): void {
    $validator = app(HexNetCategoryValidator::class);
    $main = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Trittsteine']);
    $main->characteristics()->create(['name' => 'Totholz', 'color' => '#8d6e63']);
    $year2027 = MapPointCategory::create(['group_id' => $this->child->id, 'parent_id' => $main->id, 'name' => '2027']);
    $year2028 = MapPointCategory::create(['group_id' => $this->child->id, 'parent_id' => $main->id, 'name' => '2028']);
    $other = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Solar']);
    $other->characteristics()->create(['name' => 'PV', 'color' => '#f4b400']);

    expect($validator->validate([$year2027->id, $year2028->id]))->toBeNull()
        ->and($validator->validate([]))->not->toBeNull()
        ->and($validator->validate([$year2027->id, $other->id]))->not->toBeNull();

    $year2028->characteristics()->create(['name' => 'Extra', 'color' => '#ffffff']);
    expect($validator->validate([$year2027->id, $year2028->id]))->not->toBeNull();
});

test('a new point of an active HexaNetz is found for the page after the confirmation', function (): void {
    $category = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Trittsteine']);
    $this->hexNet->categories()->sync([$category->id]);
    $point = MapPoint::factory()->create(['group_id' => $this->child->id, 'category_id' => $category->id, 'lat' => 49.8728, 'lng' => 8.6512]);
    $resolver = app(HexNetResolver::class);

    expect($resolver->forMapPoint($point))->toBeNull();

    $this->hexNet->update(['status' => HexNetStatus::ACTIVE]);
    expect($resolver->forMapPoint($point)?->is($this->hexNet))->toBeTrue();
});

test('categories and forms of other groups cannot be chosen', function (): void {
    hexNetGroupAdmin($this->child);
    $foreign = MapPointCategory::create(['group_id' => $this->sibling->id, 'name' => 'Fremd']);
    $foreign->characteristics()->create(['name' => 'Totholz', 'color' => '#8d6e63']);
    $foreignForm = app(FormDefinitionService::class)->createFromTemplate('map_point', $this->sibling->uuid, $foreign);

    $this->put(route('hexnets.update-step', [$this->hexNet, 'categories']), ['category_ids' => [$foreign->uuid]])->assertSessionHasErrors('category_ids');
    $this->put(route('hexnets.update-step', [$this->hexNet, 'form']), ['form_definition_id' => $foreignForm->uuid])->assertSessionHasErrors('form_definition_id');

    expect($this->hexNet->categories()->count())->toBe(0)
        ->and($this->hexNet->fresh()->form_definition_id)->toBeNull();
});

test('a locked grid also protects the jitter, which has a minimum', function (): void {
    hexNetGroupAdmin($this->child);
    $data = ['hex_radius_m' => 150, 'grid_locked' => false, 'threshold_medium' => 2, 'threshold_dark' => 3, 'jitter_m' => 0];

    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), $data)->assertSessionHasErrors('jitter_m');

    $this->hexNet->update(['grid_locked' => true]);
    $this->put(route('hexnets.update-step', [$this->hexNet, 'grid']), [...$data, 'grid_locked' => false, 'jitter_m' => 60])->assertSessionHasErrors('jitter_m');
});

test('play areas with too many cells are rejected', function (): void {
    hexNetGroupAdmin($this->child);
    $huge = ['coordinates' => [['lat' => 49, 'lng' => 7], ['lat' => 49, 'lng' => 10], ['lat' => 51, 'lng' => 10], ['lat' => 51, 'lng' => 7]]];

    $this->put(route('hexnets.update-step', [$this->hexNet, 'play-area']), ['play_area' => $huge])->assertSessionHasErrors('play_area.coordinates');
});

test('the grid preview counts only within the saved play area', function (): void {
    hexNetGroupAdmin($this->child);
    $category = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Trittsteine']);
    $this->hexNet->categories()->sync([$category->id]);
    MapPoint::factory()->create(['group_id' => $this->child->id, 'category_id' => $category->id, 'lat' => 49.8728, 'lng' => 8.6512]);
    MapPoint::factory()->create(['group_id' => $this->child->id, 'category_id' => $category->id, 'lat' => 49.95, 'lng' => 8.6512]);

    $response = $this->postJson(route('hexnets.grid-preview', $this->hexNet), ['hex_radius_m' => 300, 'play_area' => ['coordinates' => [['lat' => 49.9, 'lng' => 8.6], ['lat' => 49.9, 'lng' => 8.7], ['lat' => 50, 'lng' => 8.7]]]])->assertOk();

    expect(array_sum($response->json('counts')))->toBe(1);
    $this->postJson(route('hexnets.grid-preview', $this->hexNet), ['hex_radius_m' => 155])->assertUnprocessable();

    hexNetGroupAdmin($this->sibling);
    $this->postJson(route('hexnets.grid-preview', $this->hexNet), ['hex_radius_m' => 300])->assertForbidden();
});

test('a running HexaNetz goes back to draft when its only category is deleted', function (): void {
    $category = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Trittsteine']);
    $this->hexNet->categories()->sync([$category->id]);
    $this->hexNet->update(['status' => HexNetStatus::ACTIVE]);

    $category->delete();

    expect($this->hexNet->fresh()->status)->toBe(HexNetStatus::DRAFT);
});

test('points in the play area whose cell is not a play-area cell neither count nor get the highlight', function (): void {
    $category = MapPointCategory::create(['group_id' => $this->child->id, 'name' => 'Trittsteine']);
    $this->hexNet->categories()->sync([$category->id]);
    $this->hexNet->update(['status' => HexNetStatus::ACTIVE]);
    $grid = $this->hexNet->grid();
    $cells = array_flip(app(HexNetGridService::class)->cellIds($this->hexNet));

    $edge = null;
    for ($lng = 8.6413; $lng < 8.6611 && $edge === null; $lng += 0.00005) {
        $candidate = new Coordinate(49.8628 + 0.00001, $lng);
        if ($grid->contains($this->hexNet->play_area, $candidate) && ! isset($cells[$grid->cellOf($candidate)])) {
            $edge = $candidate;
        }
    }
    expect($edge)->not->toBeNull();

    $point = MapPoint::factory()->create(['group_id' => $this->child->id, 'category_id' => $category->id, 'lat' => $edge->lat, 'lng' => $edge->lng]);

    expect(app(HexNetGridService::class)->counts($this->hexNet))->toBe([])
        ->and(app(HexNetResolver::class)->forMapPoint($point))->toBeNull();
});
