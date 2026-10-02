<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReorderMapPointCharacteristicsRequest;
use App\Http\Requests\UpsertMapPointCharacteristicRequest;
use App\Models\FormField;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Characteristics ("Maßnahmen") are managed on the page of their category.
 */
class MapPointCharacteristicController extends Controller
{
    public function store(UpsertMapPointCharacteristicRequest $request, MapPointCategory $mappointCategory): RedirectResponse
    {
        $data = [
            ...$request->getData(),
            'sort_order' => (int) $mappointCategory->characteristics()->max('sort_order') + 1,
        ];

        if ($request->hasFile('icon')) {
            $data['icon_path'] = $request->file('icon')->store('characteristics', 'public');
        }

        $mappointCategory->characteristics()->create($data);

        return redirect()->back()->with('success', 'Die Maßnahme wurde angelegt');
    }

    public function update(UpsertMapPointCharacteristicRequest $request, MapPointCharacteristic $mapPointCharacteristic): RedirectResponse
    {
        $data = $request->getData();
        $formerIconPath = $mapPointCharacteristic->icon_path;

        if ($request->hasFile('icon')) {
            $data['icon_path'] = $request->file('icon')->store('characteristics', 'public');
        } elseif ($request->boolean('remove_icon')) {
            $data['icon_path'] = null;
        }

        DB::transaction(function () use ($mapPointCharacteristic, $data, $request): void {
            $mapPointCharacteristic->update($data);
            $mapPointCharacteristic->syncPublicFields(FormField::whereIn('uuid', $request->publicFieldIds())->pluck('id')->all());
        });

        if ($formerIconPath !== null && $mapPointCharacteristic->icon_path !== $formerIconPath) {
            Storage::disk('public')->delete($formerIconPath);
        }

        return redirect()->back()->with('success', 'Die Maßnahme wurde gespeichert');
    }

    public function reorder(ReorderMapPointCharacteristicsRequest $request, MapPointCategory $mappointCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $mappointCategory): void {
            foreach ($request->ids() as $sortOrder => $uuid) {
                $mappointCategory->characteristics()->where('uuid', $uuid)->update(['sort_order' => $sortOrder]);
            }
        });

        return redirect()->back();
    }

    /**
     * Opens the form builder for the characteristic's fields. The form definition holding them is created on first use.
     */
    public function editFields(MapPointCharacteristic $mapPointCharacteristic): RedirectResponse
    {
        $this->authorize('update', $mapPointCharacteristic->category);

        return redirect()->route('form-definitions.edit', $mapPointCharacteristic->findOrCreateFormDefinition());
    }

    public function destroy(MapPointCharacteristic $mapPointCharacteristic): RedirectResponse
    {
        $this->authorize('update', $mapPointCharacteristic->category);
        $name = $mapPointCharacteristic->name;

        $mapPointCharacteristic->delete();

        return redirect()->back()->with('info', 'Die Maßnahme '.e($name).' wurde gelöscht');
    }
}
