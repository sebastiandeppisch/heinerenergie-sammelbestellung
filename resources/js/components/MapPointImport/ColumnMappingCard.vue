<script setup lang="ts">
import type { ColumnTarget, DefaultVisibility, MainCategorySelection } from '@/composables/useMapPointColumnMapping';
import { keyOptionsFor, newFieldTarget, newFieldTypes, sampleValues, targetOf, unassignedIdHeader } from '@/composables/useMapPointColumnMapping';
import { Input } from '@/shadcn/components/ui/input';
import { flattenCategoryTree } from '@/utils/categoryTree';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { computed } from 'vue';

type Field = App.Enums.MapPointSpreadsheetField;

const props = defineProps<{
    upload: App.Data.SpreadsheetUploadData;
    fields: Array<App.Data.MapPointSpreadsheetFieldData>;
    categories: Array<App.Data.MapPointCategoryData>;
}>();

const columnFields = defineModel<Array<ColumnTarget>>('columnFields', { required: true });
const keyField = defineModel<Field>('keyField', { required: true });
const defaultVisibility = defineModel<DefaultVisibility>('defaultVisibility', { required: true });
const mainCategory = defineModel<MainCategorySelection>('mainCategory', { required: true });
const newMainCategoryName = defineModel<string>('newMainCategoryName', { required: true });

const categoryEntries = computed(() => flattenCategoryTree(props.categories));
const hasMainCategory = computed(() => mainCategory.value !== 'none');

defineSlots<{
    /** Loading and saving column templates, shown above the mapping. */
    templates(): unknown;
}>();

const keyOptions = computed(() => keyOptionsFor(props.fields, columnFields.value));
const idColumnAssigned = computed(() => columnFields.value.includes('id'));
const publishedColumnAssigned = computed(() => columnFields.value.includes('published'));
const unassignedId = computed(() => unassignedIdHeader(props.upload.headers, columnFields.value));

/** The mapping belongs to the session, so a change is handed up as a new list instead of editing the old one. */
const pointFields = computed(() => props.fields.filter((field) => !field.category_field_id));
const categoryFields = computed(() => props.fields.filter((field) => field.category_field_id));

function assignField(columnIndex: number, field: ColumnTarget) {
    columnFields.value = columnFields.value.map((current, index) => (index === columnIndex ? field : current));
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>2. Spalten zuordnen</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <slot name="templates" />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="key_field">Vorhandene Punkte erkennen an</Label>
                    <Select id="key_field" v-model="keyField">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in keyOptions" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-gray-500">
                        Passt der Wert einer Zeile zu einem vorhandenen Kartenpunkt, wird dieser aktualisiert, sonst wird ein neuer Punkt angelegt.
                        Der Wert selbst wird dabei nicht geändert.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="default_published">Neue Punkte sind</Label>
                    <Select id="default_published" v-model="defaultVisibility" :disabled="publishedColumnAssigned">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="private">Nicht veröffentlicht</SelectItem>
                            <SelectItem value="public">Öffentlich</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-gray-500" data-test="visibility-hint">
                        <template v-if="publishedColumnAssigned">
                            Eine Spalte ist dem Feld „Veröffentlicht“ zugeordnet, sie entscheidet je Zeile.
                        </template>
                        <template v-else> Gilt nur für Punkte, die neu angelegt werden. Vorhandene Punkte behalten ihren Stand. </template>
                    </p>
                </div>
            </div>

            <div class="space-y-2" data-test="main-category">
                <Label for="main_category">Hauptkategorie</Label>
                <div class="grid gap-2 md:grid-cols-2">
                    <Select id="main_category" v-model="mainCategory">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Keine</SelectItem>
                            <SelectItem
                                v-for="{ category, depth } in categoryEntries"
                                :key="category.id"
                                :value="category.id"
                                :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                            >
                                {{ category.name }}
                            </SelectItem>
                            <SelectItem value="new">Neue Kategorie anlegen …</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        v-if="mainCategory === 'new'"
                        v-model="newMainCategoryName"
                        placeholder="Name der neuen Kategorie"
                        data-test="new-main-category-name"
                    />
                </div>
                <p class="text-xs text-gray-500">
                    Optional. Die Punkte gehören dann zu dieser Kategorie: Die Kategorie-Spalte enthält ihre Unterkategorien, unbekannte werden als
                    Unterkategorie angelegt und übernehmen ihre Zusatzfelder. Ohne Kategorie-Spalte oder bei leerer Zelle kommen die Punkte in die
                    Hauptkategorie, Punkte in einer ihrer Unterkategorien bleiben dort. Spalten kannst du als neues Zusatzfeld der Hauptkategorie
                    anlegen. Alles entsteht erst mit dem Import.
                </p>
            </div>

            <p v-if="idColumnAssigned" class="text-sm text-gray-600" data-test="id-assigned-hint">
                Eine Spalte enthält die IDs aus einem Export, deshalb werden vorhandene Punkte an der ID erkannt. Stammt die Datei aus einer anderen
                Initiative und sollen die Punkte hier neu angelegt werden, stelle diese Spalte auf „Ignorieren“.
            </p>
            <p v-else-if="unassignedId" class="text-sm text-gray-600" data-test="id-unassigned-hint">
                Die Spalte „{{ unassignedId }}“ enthält keine IDs aus einem Export dieser Anwendung und wird deshalb nicht dem Feld „ID“ zugeordnet.
                Eine eigene Nummerierung wird nicht gebraucht.
            </p>

            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Spalte in der Datei</TableHead>
                            <TableHead>Beispielwerte</TableHead>
                            <TableHead class="w-64">Feld</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(header, index) in upload.headers" :key="`${index}-${header}`" data-test="column-row">
                            <TableCell class="font-medium">{{ header }}</TableCell>
                            <TableCell class="max-w-sm truncate text-gray-500">{{ sampleValues(upload.preview_rows, index) || '–' }}</TableCell>
                            <TableCell>
                                <Select :model-value="columnFields[index]" @update:model-value="assignField(index, $event as ColumnTarget)">
                                    <SelectTrigger class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="field in pointFields" :key="targetOf(field)" :value="targetOf(field)">
                                            {{ field.label }}
                                        </SelectItem>
                                        <SelectGroup v-if="categoryFields.length > 0">
                                            <SelectLabel>Zusatzfelder</SelectLabel>
                                            <SelectItem v-for="field in categoryFields" :key="targetOf(field)" :value="targetOf(field)">
                                                {{ field.label }}
                                            </SelectItem>
                                        </SelectGroup>
                                        <SelectGroup v-if="hasMainCategory">
                                            <SelectLabel>Als neues Zusatzfeld anlegen</SelectLabel>
                                            <SelectItem
                                                v-for="option in newFieldTypes"
                                                :key="option.type"
                                                :value="newFieldTarget(option.type)"
                                                :data-test="`new-field-${option.type}`"
                                            >
                                                Neues Feld „{{ header }}“ ({{ option.label }})
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
