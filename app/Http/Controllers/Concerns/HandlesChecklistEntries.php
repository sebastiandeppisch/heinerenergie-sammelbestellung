<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\StoreChecklistEntryRequest;
use App\Http\Requests\UpdateChecklistEntryRequest;
use App\Models\Advice;
use App\Models\ChecklistEntry;
use Illuminate\Support\Facades\DB;

trait HandlesChecklistEntries
{
    public function storeChecklistEntry(Advice $advice, StoreChecklistEntryRequest $request)
    {
        $checklist = $request->checklist();
        $checklist->loadMissing('fields.options');

        DB::transaction(function () use ($advice, $checklist) {
            $entry = $advice->checklistEntries()->create([
                'form_definition_id' => $checklist->id,
            ]);

            foreach ($checklist->fields as $field) {
                $field->createChecklistEntryField($entry);
            }
        });

        return back();
    }

    public function updateChecklistEntry(Advice $advice, ChecklistEntry $checklistEntry, UpdateChecklistEntryRequest $request)
    {
        $checklistEntry->loadMissing('fields');
        $fieldsByUuid = $checklistEntry->fields->keyBy('uuid');

        DB::transaction(function () use ($fieldsByUuid, $request) {
            foreach ($request->fieldValues() as $uuid => $value) {
                $field = $fieldsByUuid->get($uuid);
                if ($field === null) {
                    continue;
                }
                $field->update(['value' => $value]);
            }
        });

        return back();
    }
}
