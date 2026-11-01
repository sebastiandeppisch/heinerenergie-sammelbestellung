<?php

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetCategoryValidator;
use App\HexNet\Services\HexNetResolver;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\SessionService;
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
