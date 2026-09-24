<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/shadcn/components/ui/tooltip';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { useFillViewportHeight } from '@/composables/useFillViewportHeight';

import MapPointCategory from '@/components/MapPointCategory.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { Download, FileUp, Map, Pencil, Plus, Trash } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPoints: Array<App.Data.MapPointData>;
    categories: Array<App.Data.MapPointCategoryData>;
    canImportAndExport: boolean;
    importAndExportNeedGroup: boolean;
    spreadsheetMappings: Array<App.Data.MapPointSpreadsheetMappingData>;
    spreadsheetFormats: Array<App.Data.SpreadsheetFormatData>;
}>();

const rootEl = ref<HTMLElement | null>(null);
const { height: rootHeight } = useFillViewportHeight(rootEl);

const showExportDialog = ref(false);
const exportMappingId = ref<string | null>(null);
const exportFormat = ref<App.Enums.SpreadsheetFormat>('xlsx');

const exportUrl = computed(() =>
    route('mappoints.export', {
        format: exportFormat.value,
        ...(exportMappingId.value ? { mapping: exportMappingId.value } : {}),
    }),
);
const searchQuery = ref('');

// Filter map points based on search query
const filteredMapPoints = computed(() => {
    if (!searchQuery.value) return props.mapPoints;

    const query = searchQuery.value.toLowerCase();
    return props.mapPoints.filter(
        (point) =>
            point.title.toLowerCase().includes(query) ||
            point.description.toLowerCase().includes(query) ||
            point.userReadablePointableType.toLowerCase().includes(query),
    );
});

// State for delete confirmation dialog
const showDeleteDialog = ref(false);
const pointIdToDelete = ref<string | null>(null);

function confirmDelete(id: string) {
    pointIdToDelete.value = id;
    showDeleteDialog.value = true;
}

function deleteMapPoint() {
    if (pointIdToDelete.value) {
        router.delete(route('mappoints.destroy', pointIdToDelete.value), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                pointIdToDelete.value = null;
                toast('Der Kartenpunkte wurde gelöscht');
            },
        });
    }
}
</script>

<template>
    <div ref="rootEl" data-test="mappoints-root" class="flex flex-col" :style="{ height: rootHeight }">
        <div class="mx-2 mt-4 mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Karten Punkte</h1>
            <div class="flex gap-4">
                <Input v-model="searchQuery" placeholder="Suche..." class="max-w-sm bg-white" />
                <TooltipProvider v-if="canImportAndExport && importAndExportNeedGroup">
                    <Tooltip>
                        <!-- Disabled buttons fire no pointer events, so the wrapper opens the tooltip. -->
                        <TooltipTrigger as-child>
                            <span class="flex gap-4" tabindex="0">
                                <Button variant="outline" disabled><FileUp />Importieren</Button>
                                <Button variant="outline" disabled><Download />Exportieren</Button>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent
                            >Bitte wähle zuerst eine Initiative aus. Import und Export arbeiten immer mit den Kartenpunkten einer
                            Initiative.</TooltipContent
                        >
                    </Tooltip>
                </TooltipProvider>
                <template v-else-if="canImportAndExport">
                    <Link :href="route('mappoints.import.create')">
                        <Button variant="outline"><FileUp />Importieren</Button>
                    </Link>
                    <Button variant="outline" @click="showExportDialog = true"><Download />Exportieren</Button>
                </template>
                <Link :href="route('mappoint-categories.index')">
                    <Button variant="outline">Kategorien verwalten</Button>
                </Link>
                <Link :href="route('map-embeds.index')">
                    <Button variant="outline"><Map />Einbettungen verwalten</Button>
                </Link>
                <Link :href="route('mappoints.create')">
                    <Button><Plus />Neuen Punkt hinzufügen</Button>
                </Link>
            </div>
        </div>
        <Card class="mx-2 min-h-0 flex-1 p-4">
            <Table>
                <TableCaption>Liste aller Kartenpunkte</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Titel</TableHead>
                        <TableHead>Kategorie</TableHead>
                        <TableHead>Ursprung</TableHead>
                        <TableHead>Beschreibung</TableHead>
                        <TableHead>Ort</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Erstellt am</TableHead>
                        <TableHead>Aktionen</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="point in filteredMapPoints" :key="point.id" class="odd:bg-white even:bg-gray-50">
                        <TableCell class="min-w-48 font-medium whitespace-normal">{{ point.title }}</TableCell>
                        <TableCell class="whitespace-normal">
                            <div v-if="point.category_id !== null" class="flex items-center gap-2">
                                <MapPointCategory :category_id="point.category_id" :allCategories="props.categories" :show-name="true" />
                            </div>
                            <span v-else class="text-gray-500 italic">Keine Kategorie</span>
                        </TableCell>
                        <TableCell>{{ point.userReadablePointableType }}</TableCell>
                        <TableCell class="max-w-xs truncate">{{ point.description }}</TableCell>
                        <TableCell class="min-w-40 whitespace-normal">{{
                            point.location || `${point.coordinate.lat.toFixed(6)}, ${point.coordinate.lng.toFixed(6)}`
                        }}</TableCell>
                        <TableCell>
                            <span
                                :class="{
                                    'rounded px-2 py-1 text-xs font-medium': true,
                                    'bg-green-100 text-green-800': point.published,
                                    'bg-red-100 text-red-800': !point.published,
                                }"
                            >
                                {{ point.published ? 'Öffentlich' : 'Eingereicht' }}
                            </span>
                        </TableCell>
                        <TableCell>
                            {{ new Date(point.created_at).toLocaleDateString('de-DE') }}
                        </TableCell>
                        <TableCell>
                            <div class="flex space-x-2">
                                <Link :href="route('mappoints.edit', point.id)">
                                    <Button variant="outline" size="sm"><Pencil /></Button>
                                </Link>
                                <Button variant="destructive" size="sm" @click="confirmDelete(point.id)">
                                    <Trash />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="filteredMapPoints.length === 0">
                        <TableCell colspan="8" class="py-8 text-center text-gray-500"> Keine Punkte gefunden </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <Dialog v-model:open="showExportDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Kartenpunkte exportieren</DialogTitle>
                    <DialogDescription>
                        Exportiert die Punkte dieser Initiative und ihrer Unterinitiativen. Die ID-Spalte steht immer vorne, damit die bearbeitete
                        Datei wieder importiert werden kann.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="export_mapping">Spaltenvorlage</Label>
                        <Select id="export_mapping" v-model="exportMappingId">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Alle Felder" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Alle Felder</SelectItem>
                                <SelectItem v-for="mapping in spreadsheetMappings" :key="mapping.id" :value="mapping.id">{{
                                    mapping.name
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-gray-500">
                            Spaltenvorlagen legst du beim Import an. Sie bestimmen, welche Spalten die Datei enthält und wie sie heißen.
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="export_format">Format</Label>
                        <Select id="export_format" v-model="exportFormat">
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="format in spreadsheetFormats" :key="format.value" :value="format.value">{{
                                    format.label
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showExportDialog = false">Abbrechen</Button>
                    <!-- A real file download, so deliberately not an Inertia visit. -->
                    <Button as="a" :href="exportUrl" @click="showExportDialog = false"><Download />Herunterladen</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Punkt wirklich löschen?</DialogTitle>
                    <DialogDescription> Der Punkt wird unwiederbringlich gelöscht </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Abbrechen</Button>
                    <Button variant="destructive" @click="deleteMapPoint">Löschen</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
