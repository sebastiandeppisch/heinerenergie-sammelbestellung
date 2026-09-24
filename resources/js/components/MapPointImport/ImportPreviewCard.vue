<script setup lang="ts">
import type { MapPointImportPayload } from '@/composables/useMapPointColumnMapping';
import { samePayload } from '@/composables/useMapPointColumnMapping';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { useForm, useHttp } from '@inertiajs/vue3';
import { FileUp, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    payload: MapPointImportPayload;
}>();

/** The preview answers with data instead of a page, so it runs as a plain HTTP request. */
const previewRequest = useHttp<MapPointImportPayload, App.Data.MapPointImportResultData>({ ...props.payload });

const importForm = useForm<MapPointImportPayload>({ ...props.payload });

/** The mapping the shown preview was made with. Only a successful preview sets it. */
const previewedPayload = ref<MapPointImportPayload | null>(null);

/** Validation errors arrive in the request itself, this covers a server that could not answer at all. */
const previewFailed = ref(false);

const preview = computed(() => (previewedPayload.value === null ? null : previewRequest.response));

/** The import may only run with exactly the mapping that was checked in the preview. */
const previewIsCurrent = computed(() => previewedPayload.value !== null && samePayload(previewedPayload.value, props.payload));

const canRunImport = computed(
    () => previewIsCurrent.value && preview.value !== null && preview.value.errors.length === 0 && preview.value.rows.length > 0,
);

const requestErrors = computed(() => [...Object.values(previewRequest.errors), ...Object.values(importForm.errors)]);

async function runPreview() {
    const payload = props.payload;
    previewedPayload.value = null;
    previewFailed.value = false;
    previewRequest.clearErrors();

    await previewRequest
        .transform(() => payload)
        .post(route('mappoints.import.preview'), {
            onSuccess: () => (previewedPayload.value = payload),
        })
        .catch(() => (previewFailed.value = true));
}

/** Preserving the state keeps mapping and preview when the import is refused, so the user can fix and retry. */
function runImport() {
    importForm.transform(() => props.payload).post(route('mappoints.import.store'), { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>3. Prüfen und importieren</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" :disabled="previewRequest.processing" data-test="run-preview" @click="runPreview">
                    <Search />
                    {{ previewRequest.processing ? 'Wird geprüft...' : 'Vorschau erstellen' }}
                </Button>
                <Button :disabled="!canRunImport || importForm.processing" data-test="run-import" @click="runImport">
                    <FileUp />
                    {{ importForm.processing ? 'Wird importiert...' : 'Import ausführen' }}
                </Button>
                <span v-if="previewedPayload && !previewIsCurrent" class="text-sm text-amber-600" data-test="preview-outdated">
                    Die Zuordnung wurde geändert. Bitte erstelle die Vorschau neu.
                </span>
            </div>

            <p v-for="message in requestErrors" :key="message" class="text-sm text-red-500" data-test="request-error">{{ message }}</p>
            <p v-if="previewFailed" class="text-sm text-red-500" data-test="request-error">Die Vorschau konnte nicht erstellt werden.</p>

            <template v-if="preview">
                <div class="flex flex-wrap gap-2" data-test="preview-counts">
                    <Badge variant="secondary">{{ preview.created_count }} neu</Badge>
                    <Badge variant="secondary">{{ preview.updated_count }} aktualisiert</Badge>
                    <Badge v-if="preview.errors.length > 0" variant="destructive">{{ preview.errors.length }} Fehler</Badge>
                </div>

                <p v-if="preview.created_categories.length > 0" class="text-sm text-gray-700">
                    Neue Kategorien: {{ preview.created_categories.join(', ') }}
                </p>

                <div v-if="preview.errors.length > 0" class="space-y-2" data-test="row-errors">
                    <p class="text-sm text-red-600">
                        Solange die Datei fehlerhafte Zeilen enthält, wird nichts importiert. Korrigiere die Datei oder die Zuordnung.
                    </p>
                    <div class="overflow-x-auto rounded-lg border border-red-200">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-20">Zeile</TableHead>
                                    <TableHead>Spalte</TableHead>
                                    <TableHead>Fehler</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(error, index) in preview.errors" :key="index">
                                    <TableCell>{{ error.row }}</TableCell>
                                    <TableCell>{{ error.column ?? '–' }}</TableCell>
                                    <TableCell>{{ error.message }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div v-if="preview.rows.length > 0" class="overflow-x-auto rounded-lg border" data-test="preview-rows">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-20">Zeile</TableHead>
                                <TableHead>Aktion</TableHead>
                                <TableHead>Titel</TableHead>
                                <TableHead>Initiative</TableHead>
                                <TableHead>Kategorie</TableHead>
                                <TableHead>Ort</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in preview.rows" :key="row.row">
                                <TableCell>{{ row.row }}</TableCell>
                                <TableCell>
                                    <Badge :variant="row.is_update ? 'outline' : 'secondary'">{{ row.is_update ? 'Aktualisiert' : 'Neu' }}</Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ row.title }}</TableCell>
                                <TableCell>{{ row.group_name }}</TableCell>
                                <TableCell>{{ row.category ?? '–' }}</TableCell>
                                <TableCell class="max-w-xs truncate">{{ row.location ?? `${row.lat}, ${row.lng}` }}</TableCell>
                                <TableCell>{{ row.published ? 'Öffentlich' : 'Nicht veröffentlicht' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </template>
        </CardContent>
    </Card>
</template>
