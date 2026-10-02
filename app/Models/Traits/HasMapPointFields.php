<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Owners of map point fields: categories and characteristics. The fields live in a form definition of type
 * FormType::MapPointFields, the values on the points.
 *
 * @property int|null $form_definition_id
 */
trait HasMapPointFields
{
    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * The own fields that are shown on the public map. All other fields stay internal.
     *
     * @return Builder<FormField>
     */
    public function publicFields(): Builder
    {
        return FormField::query()
            ->where('form_definition_id', $this->form_definition_id)
            ->whereIn('id', DB::table('map_point_public_fields')->select('form_field_id'));
    }

    /**
     * Marks exactly the given own fields as public. The caller ensures the fields belong to the owner.
     *
     * @param  array<int, int>  $formFieldIds
     */
    public function syncPublicFields(array $formFieldIds): void
    {
        DB::transaction(function () use ($formFieldIds): void {
            DB::table('map_point_public_fields')
                ->whereIn('form_field_id', FormField::query()->where('form_definition_id', $this->form_definition_id)->select('id'))
                ->delete();

            DB::table('map_point_public_fields')->insert(array_map(fn (int $formFieldId): array => [
                'form_field_id' => $formFieldId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $formFieldIds));
        });
    }

    /**
     * The form definition holding the own fields, created on first use.
     */
    public function findOrCreateFormDefinition(): FormDefinition
    {
        if ($this->formDefinition !== null) {
            return $this->formDefinition;
        }

        $formDefinition = FormDefinition::create([
            'name' => $this->formDefinitionName(),
            'group_id' => $this->fieldsGroupId(),
            'type' => FormType::MapPointFields,
            'is_active' => true,
        ]);

        $this->formDefinition()->associate($formDefinition)->save();

        return $formDefinition;
    }

    abstract protected function formDefinitionName(): string;

    abstract protected function fieldsGroupId(): int;
}
