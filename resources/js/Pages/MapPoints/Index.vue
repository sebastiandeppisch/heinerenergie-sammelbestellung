<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/shadcn/components/ui/tooltip';
import { Link, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { useExpandedIds } from '@/composables/useExpandedIds';
import { useFillViewportHeight } from '@/composables/useFillViewportHeight';

import MapPointCategory from '@/components/MapPointCategory.vue';
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { ChevronDown, ChevronRight, Download, FileUp, Map, Pencil, Plus, Trash } from '@lucide/vue';
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

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte' }, { title: 'Tabelle' }],
});

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
            point.userReadablePointableType.toLowerCase().includes(query) ||
            point.fields.some((field) => field.display_value.toLowerCase().includes(query)),
    );
});

/** The category fields are shown in a detail row, so the table does not get wider with every field. */
const { isExpanded, toggleExpanded } = useExpandedIds();

function hasFieldValues(point: App.Data.MapPointData): boolean {
    return point.fields.length > 0 || point.former_fields.length > 0;
}

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
        <PageHeader title="Kartenpunkte">
            <template #actions>
                <Input v-model="searchQuery" placeholder="Suche..." class="w-56 bg-white" />
                <TooltipProvider v-if="canImportAndExport && importAndExportNeedGroup">
                    <Tooltip>
                        <!-- Disabled buttons fire no pointer events, so the wrapper opens the tooltip. -->
                        <TooltipTrigger as-child>
                            <span class="flex gap-2" tabindex="0">
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
            </template>
        </PageHeader>
        <Card class="min-h-0 flex-1 p-4">
            <Table>
                <TableCaption>Liste aller Kartenpunkte</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-8"><span class="sr-only">Details</span></TableHead>
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
                    <template v-for="point in filteredMapPoints" :key="point.id">
                        <TableRow class="odd:bg-white even:bg-gray-50">
                            <TableCell class="w-8 px-1">
                                <Button
                                    v-if="hasFieldValues(point)"
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :aria-expanded="isExpanded(point.id)"
                                    :aria-label="isExpanded(point.id) ? 'Zusatzfelder ausblenden' : 'Zusatzfelder anzeigen'"
                                    data-test="toggle-point-details"
                                    @click="toggleExpanded(point.id)"
                                >
                                    <ChevronDown v-if="isExpanded(point.id)" class="h-4 w-4" />
                                    <ChevronRight v-else class="h-4 w-4" />
                                </Button>
                            </TableCell>
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
                        <TableRow v-if="isExpanded(point.id)" class="bg-muted/30 hover:bg-muted/30">
                            <TableCell />
                            <TableCell colspan="8" class="whitespace-normal">
                                <div class="flex flex-wrap gap-x-12 gap-y-4 py-1">
                                    <MapPointFieldList v-if="point.fields.length > 0" :fields="point.fields" mark-internal />
                                    <div v-if="point.former_fields.length > 0" class="space-y-1">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            Frühere Angaben (gehören nicht mehr zur Kategorie, nur hier sichtbar)
                                        </p>
                                        <MapPointFieldList :fields="point.former_fields" class="opacity-70" />
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-if="filteredMapPoints.length === 0">
                        <TableCell colspan="9" class="py-8 text-center text-gray-500"> Keine Punkte gefunden </TableCell>
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
