<script setup lang="ts">
import type { DefaultVisibility } from '@/composables/useMapPointColumnMapping';
import { keyOptionsFor, sampleValues, unassignedIdHeader } from '@/composables/useMapPointColumnMapping';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { computed } from 'vue';

type Field = App.Enums.MapPointSpreadsheetField;

const props = defineProps<{
    upload: App.Data.SpreadsheetUploadData;
    fields: Array<App.Data.MapPointSpreadsheetFieldData>;
}>();

const columnFields = defineModel<Array<Field>>('columnFields', { required: true });
const keyField = defineModel<Field>('keyField', { required: true });
const defaultVisibility = defineModel<DefaultVisibility>('defaultVisibility', { required: true });

defineSlots<{
    /** Loading and saving column templates, shown above the mapping. */
    templates(): unknown;
}>();

const keyOptions = computed(() => keyOptionsFor(props.fields, columnFields.value));
const idColumnAssigned = computed(() => columnFields.value.includes('id'));
const publishedColumnAssigned = computed(() => columnFields.value.includes('published'));
const unassignedId = computed(() => unassignedIdHeader(props.upload.headers, columnFields.value));

/** The mapping belongs to the session, so a change is handed up as a new list instead of editing the old one. */
function assignField(columnIndex: number, field: Field) {
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
                                <Select :model-value="columnFields[index]" @update:model-value="assignField(index, $event as Field)">
                                    <SelectTrigger class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="field in fields" :key="field.value" :value="field.value">
                                            {{ field.label }}
                                        </SelectItem>
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
