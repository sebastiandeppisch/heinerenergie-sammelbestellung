<script setup lang="ts">
import { FormItem } from '@/shadcn/components/ui/form';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { flattenCategoryTree } from '@/utils/categoryTree';
import { sourceFieldCandidates, subcategoryCandidateIds } from '@/utils/mapPointCategoryMapping';
import { computed } from 'vue';

type FormFieldData = App.Data.FormFieldData;
type FieldType = App.Enums.FieldType;

const props = defineProps<{
    /** The fields of the form, which fill the category fields. */
    formFields: Array<FormFieldData>;
    categories: Array<App.Data.MapPointCategoryData>;
    /** The fields of each category including the inherited ones, keyed by category id. */
    fieldsByCategory: Record<string, Array<FormFieldData>>;
    /** Which form field types can fill a category field of the given type. */
    fieldSourceTypes: Partial<Record<FieldType, Array<FieldType>>>;
}>();

const mapping = defineModel<App.Data.FormToMapPointMappingData>('mapping', { required: true });

/** Reka's select cannot hold null, so „nothing chosen“ gets its own value. */
const NONE = 'none';

const categoryEntries = computed(() => flattenCategoryTree(props.categories));

const categoryFields = computed(() => (mapping.value.category_id ? (props.fieldsByCategory[mapping.value.category_id] ?? []) : []));

const categorySelection = computed({
    get: () => mapping.value.category_id ?? NONE,
    set: (value: string) => {
        mapping.value.category_id = value === NONE ? null : value;
        // Sub categories and fields belong to the category, so they no longer fit another one.
        mapping.value.subcategory_field_id = null;
        mapping.value.subcategory_options = [];
        mapping.value.field_mappings = [];
    },
});

const subcategoryFieldCandidates = computed(() => props.formFields.filter((field) => field.type === 'select' || field.type === 'radio'));

const subcategoryFieldSelection = computed({
    get: () => mapping.value.subcategory_field_id ?? NONE,
    set: (value: string) => {
        mapping.value.subcategory_field_id = value === NONE ? null : value;
        mapping.value.subcategory_options = [];
    },
});

const subcategoryOptions = computed(() => props.formFields.find((field) => field.id === mapping.value.subcategory_field_id)?.options ?? []);

const subcategoryCandidates = computed(() => {
    const categoryId = mapping.value.category_id;

    if (!categoryId) {
        return [];
    }

    const candidateIds = subcategoryCandidateIds(props.categories, props.fieldsByCategory, categoryId);

    return categoryEntries.value.filter(({ category }) => candidateIds.includes(category.id));
});

function subcategoryFor(optionValue: string): string {
    return mapping.value.subcategory_options.find((option) => option.option_value === optionValue)?.category_id ?? NONE;
}

function setSubcategory(optionValue: string, categoryId: string) {
    const others = mapping.value.subcategory_options.filter((option) => option.option_value !== optionValue);
    mapping.value.subcategory_options = categoryId === NONE ? others : [...others, { option_value: optionValue, category_id: categoryId }];
}

function sourceCandidates(targetField: FormFieldData): Array<FormFieldData> {
    return sourceFieldCandidates(props.formFields, targetField, props.fieldSourceTypes);
}

function sourceFor(targetFieldId: string): string {
    return mapping.value.field_mappings.find((fieldMapping) => fieldMapping.target_field_id === targetFieldId)?.source_field_id ?? NONE;
}

function setSource(targetFieldId: string, sourceFieldId: string) {
    const others = mapping.value.field_mappings.filter((fieldMapping) => fieldMapping.target_field_id !== targetFieldId);
    mapping.value.field_mappings = sourceFieldId === NONE ? others : [...others, { target_field_id: targetFieldId, source_field_id: sourceFieldId }];
}
</script>

<template>
    <div class="space-y-4" data-test="map-point-category-mapping">
        <FormItem>
            <Label>Kategorie</Label>
            <Select v-model="categorySelection">
                <SelectTrigger data-test="map-point-category">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NONE">Keine Kategorie</SelectItem>
                    <SelectItem
                        v-for="{ category, depth } in categoryEntries"
                        :key="category.id"
                        :value="category.id"
                        :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">Die Kartenpunkte aus diesem Formular landen in dieser Kategorie.</p>
        </FormItem>

        <template v-if="mapping.category_id">
            <FormItem>
                <Label>Unterkategorie aus Formularfeld</Label>
                <Select v-model="subcategoryFieldSelection">
                    <SelectTrigger data-test="map-point-subcategory-field">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="NONE">Nicht auswählen lassen</SelectItem>
                        <SelectItem v-for="field in subcategoryFieldCandidates" :key="field.id" :value="field.id">{{ field.label }}</SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                    Optional: Mit einem Auswahlfeld wählt die ausfüllende Person eine Unterkategorie. Zur Wahl stehen nur Unterkategorien, die die
                    Felder dieser Kategorie übernehmen.
                </p>
            </FormItem>

            <div v-if="mapping.subcategory_field_id" class="space-y-3 rounded-md border border-gray-200 bg-gray-50 p-4">
                <h4 class="text-sm font-medium text-gray-900">Unterkategorie je Option</h4>
                <FormItem v-for="option in subcategoryOptions" :key="option.id">
                    <Label class="text-xs">{{ option.label }}</Label>
                    <Select :model-value="subcategoryFor(option.value)" @update:model-value="(value) => setSubcategory(option.value, String(value))">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">Kategorie selbst</SelectItem>
                            <SelectItem
                                v-for="{ category, depth } in subcategoryCandidates"
                                :key="category.id"
                                :value="category.id"
                                :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormItem>
            </div>

            <div class="space-y-3" data-test="map-point-field-mappings">
                <h4 class="text-sm font-medium">Zusatzfelder</h4>
                <p v-if="categoryFields.length === 0" class="text-sm text-muted-foreground italic">Diese Kategorie hat keine Zusatzfelder.</p>
                <template v-else>
                    <p class="text-xs text-muted-foreground">
                        Wähle, aus welchem Formularfeld ein Zusatzfeld befüllt wird. Alle Zuordnungen sind freiwillig. Bei Auswahlfeldern werden die
                        Optionen über ihre Bezeichnung zugeordnet.
                    </p>
                    <FormItem v-for="field in categoryFields" :key="field.id">
                        <Label>{{ field.label }}</Label>
                        <Select :model-value="sourceFor(field.id)" @update:model-value="(value) => setSource(field.id, String(value))">
                            <SelectTrigger :data-test="`map-point-field-source-${field.id}`">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">Nicht übernehmen</SelectItem>
                                <SelectItem v-for="source in sourceCandidates(field)" :key="source.id" :value="source.id">{{
                                    source.label
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </FormItem>
                </template>
            </div>
        </template>
    </div>
</template>
