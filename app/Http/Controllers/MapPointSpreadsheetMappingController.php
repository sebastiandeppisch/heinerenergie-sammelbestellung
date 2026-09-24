<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Http\Controllers\Concerns\RequiresCurrentGroup;
use App\Http\Requests\UpsertMapPointSpreadsheetMappingRequest;
use App\Models\MapPointSpreadsheetMapping;
use Illuminate\Http\RedirectResponse;

class MapPointSpreadsheetMappingController extends Controller
{
    use RequiresCurrentGroup;

    public function store(UpsertMapPointSpreadsheetMappingRequest $request, GroupContextContract $groupContext): RedirectResponse
    {
        MapPointSpreadsheetMapping::create([
            ...$request->mappingData(),
            'group_id' => $this->currentGroup($groupContext)->id,
        ]);

        return redirect()->back()->with('success', 'Die Spaltenvorlage wurde gespeichert');
    }

    public function update(UpsertMapPointSpreadsheetMappingRequest $request, MapPointSpreadsheetMapping $mapping): RedirectResponse
    {
        $mapping->update($request->mappingData());

        return redirect()->back()->with('success', 'Die Spaltenvorlage wurde aktualisiert');
    }

    public function destroy(MapPointSpreadsheetMapping $mapping): RedirectResponse
    {
        $this->authorize('delete', $mapping);

        $mapping->delete();

        return redirect()->back()->with('info', 'Die Spaltenvorlage '.e($mapping->name).' wurde gelöscht');
    }
}
