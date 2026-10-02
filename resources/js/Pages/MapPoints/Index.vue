<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/shadcn/components/ui/tooltip';
import { Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import { useExpandedIds } from '@/composables/useExpandedIds';
import { useFillViewportHeight } from '@/composables/useFillViewportHeight';

import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import MapPointCategory from '@/components/MapPointCategory.vue';
import MapPointCharacteristicBadge from '@/components/MapPointCharacteristics/MapPointCharacteristicBadge.vue';
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/shadcn/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/shadcn/components/ui/radio-group';
import type { CustomPageProps } from '@/types/pageProps';
import { ancestorIds, flattenCategoryTree } from '@/utils/categoryTree';
import { ChevronDown, ChevronRight, Download, Eye, EyeOff, FilePlus, FileUp, Filter, FolderInput, Map, Pencil, Plus, Tags, Trash } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPoints: Array<App.Data.MapPointData>;
    categories: Array<App.Data.MapPointCategoryData>;
    /** The categories each initiative can use, keyed by initiative id. */
    usableCategoryIdsByGroup: Record<string, Array<string>>;
    canImportAndExport: boolean;
    importAndExportNeedGroup: boolean;
    spreadsheetMappings: Array<App.Data.MapPointSpreadsheetMappingData>;
    spreadsheetFormats: Array<App.Data.SpreadsheetFormatData>;
    characteristics: Array<App.Data.MapPointCharacteristicData>;
    /** The characteristics points of each category can have, keyed by category id. */
    characteristicIdsByCategory: Record<string, Array<string>>;
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte' }, { title: 'Tabelle' }],
});

const rootEl = ref<HTMLElement | null>(null);
const { height: rootHeight } = useFillViewportHeight(rootEl);

const showExportDialog = ref(false);
const exportMappingId = ref<string | null>(null);
const exportFormat = ref<App.Enums.SpreadsheetFormat>('xlsx');
/** Either the points of all categories, or of one category with its characteristics, never mixed. */
const exportMode = ref<'several' | 'one'>('several');
const exportCategoryId = ref<string | null>(null);

const page = usePage<CustomPageProps>();

/** Like on import, only categories the current initiative can use. */
const exportCategoryEntries = computed(() => {
    const usableIds = props.usableCategoryIdsByGroup[page.props.auth.currentGroup?.id ?? ''] ?? [];

    return flattenCategoryTree(props.categories.filter((category) => usableIds.includes(category.id)));
});

const exportUrl = computed(() =>
    route('mappoints.export', {
        format: exportFormat.value,
        ...(exportMappingId.value ? { mapping: exportMappingId.value } : {}),
        ...(exportMode.value === 'one' && exportCategoryId.value ? { category: exportCategoryId.value } : {}),
    }),
);
const searchQuery = ref('');

/** Selecting a category includes its sub categories. The category page links here with ?category=<id>. */
const categorySelection = reactive<Record<string, boolean>>(
    Object.fromEntries(
        props.categories.map((category) => [category.id, category.id === new URLSearchParams(window.location.search).get('category')]),
    ),
);
const withoutCategory = ref(false);

const singleSelectedCategoryId = computed(() => {
    const selectedIds = Object.entries(categorySelection)
        .filter(([, selected]) => selected)
        .map(([id]) => id);

    return selectedIds.length === 1 && !withoutCategory.value ? selectedIds[0] : null;
});

const selectedCategoryCount = computed(() => Object.values(categorySelection).filter(Boolean).length + (withoutCategory.value ? 1 : 0));

function matchesCategoryFilter(point: App.Data.MapPointData): boolean {
    if (selectedCategoryCount.value === 0) {
        return true;
    }

    if (point.category_id === null) {
        return withoutCategory.value;
    }

    return [point.category_id, ...ancestorIds(props.categories, point.category_id)].some((id) => categorySelection[id]);
}

function resetCategoryFilter() {
    Object.keys(categorySelection).forEach((id) => (categorySelection[id] = false));
    withoutCategory.value = false;
}

// Filter map points based on search query and categories
const filteredMapPoints = computed(() => {
    const byCategory = props.mapPoints.filter(matchesCategoryFilter);

    if (!searchQuery.value) return byCategory;

    const query = searchQuery.value.toLowerCase();
    return byCategory.filter(
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

/**
 * Points are selected for deleting many at once. Only points the filters show are deleted, so a selection
 * made before changing the filter cannot remove points that are no longer in view.
 */
const selectedPointIds = ref(new Set<string>());
const selectedVisibleIds = computed(() => filteredMapPoints.value.filter((point) => selectedPointIds.value.has(point.id)).map((point) => point.id));
const allVisibleSelected = computed(() => filteredMapPoints.value.length > 0 && selectedVisibleIds.value.length === filteredMapPoints.value.length);

function togglePoint(pointId: string, checked: boolean | 'indeterminate') {
    const ids = new Set(selectedPointIds.value);
    if (checked === true) {
        ids.add(pointId);
    } else {
        ids.delete(pointId);
    }
    selectedPointIds.value = ids;
}

function toggleAllVisible(checked: boolean | 'indeterminate') {
    selectedPointIds.value = new Set(checked === true ? filteredMapPoints.value.map((point) => point.id) : []);
}

const showBulkDeleteDialog = ref(false);

const showCategoryDialog = ref(false);
/** Reka's select cannot hold null, so „no category“ gets its own value. */
const NO_CATEGORY = 'none';
const targetCategoryId = ref(NO_CATEGORY);

/** Only categories usable in the initiatives of all selected points, as the server requires. */
const targetCategoryEntries = computed(() => {
    const groupIds = new Set(props.mapPoints.filter((point) => selectedVisibleIds.value.includes(point.id)).map((point) => point.group_id));
    const usableIds = [...groupIds].map((groupId) => props.usableCategoryIdsByGroup[groupId] ?? []);

    return flattenCategoryTree(props.categories.filter((category) => usableIds.every((ids) => ids.includes(category.id))));
});

function setSelectedPublished(published: boolean) {
    router.patch(
        route('mappoints.update-published-of-many'),
        { ids: selectedVisibleIds.value, published },
        {
            preserveScroll: true,
            onSuccess: () => (selectedPointIds.value = new Set()),
            onError: (errors) => Object.values(errors).forEach((error) => toast.error(error)),
        },
    );
}

function openCategoryDialog() {
    targetCategoryId.value = NO_CATEGORY;
    showCategoryDialog.value = true;
}

function moveSelectedPoints() {
    router.patch(
        route('mappoints.update-category-of-many'),
        { ids: selectedVisibleIds.value, category_id: targetCategoryId.value === NO_CATEGORY ? null : targetCategoryId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCategoryDialog.value = false;
                selectedPointIds.value = new Set();
            },
            onError: (errors) => Object.values(errors).forEach((error) => toast.error(error)),
        },
    );
}

const showCharacteristicDialog = ref(false);
const targetCharacteristicId = ref<string | null>(null);

const selectedPoints = computed(() => props.mapPoints.filter((point) => selectedVisibleIds.value.includes(point.id)));

/** Characteristics the categories of all selected points offer, as the server requires for adding. */
const addableCharacteristicIds = computed(() => {
    const idsPerPoint = selectedPoints.value.map((point) => (point.category_id ? (props.characteristicIdsByCategory[point.category_id] ?? []) : []));

    return props.characteristics.map((characteristic) => characteristic.id).filter((id) => idsPerPoint.every((ids) => ids.includes(id)));
});

const removableCharacteristicIds = computed(
    () => new Set(selectedPoints.value.flatMap((point) => point.characteristics.map((characteristic) => characteristic.id))),
);

const targetCharacteristics = computed(() =>
    props.characteristics.filter(
        (characteristic) => addableCharacteristicIds.value.includes(characteristic.id) || removableCharacteristicIds.value.has(characteristic.id),
    ),
);

function openCharacteristicDialog() {
    targetCharacteristicId.value = null;
    showCharacteristicDialog.value = true;
}

function updateSelectedCharacteristic(action: 'add' | 'remove') {
    router.patch(
        route('mappoints.update-characteristic-of-many'),
        { ids: selectedVisibleIds.value, characteristic_id: targetCharacteristicId.value, action },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCharacteristicDialog.value = false;
                selectedPointIds.value = new Set();
            },
            onError: (errors) => Object.values(errors).forEach((error) => toast.error(error)),
        },
    );
}

function deleteSelectedPoints() {
    router.delete(route('mappoints.destroy-many'), {
        data: { ids: selectedVisibleIds.value },
        preserveScroll: true,
        onSuccess: () => {
            showBulkDeleteDialog.value = false;
            selectedPointIds.value = new Set();
        },
    });
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
                <!-- Opens the form wizard in the form management, with the category of the filter when exactly one is chosen. -->
                <Link
                    :href="
                        route('form-definitions.index', {
                            template: 'map_point',
                            ...(singleSelectedCategoryId ? { category: singleSelectedCategoryId } : {}),
                        })
                    "
                >
                    <Button variant="outline"><FilePlus />Formular erstellen</Button>
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
            <div class="flex flex-wrap items-center gap-2">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button variant="outline" size="sm" data-test="category-filter">
                            <Filter />
                            {{
                                selectedCategoryCount === 0
                                    ? 'Alle Kategorien'
                                    : selectedCategoryCount === 1
                                      ? '1 Kategorie gewählt'
                                      : `${selectedCategoryCount} Kategorien gewählt`
                            }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="max-h-96 w-72 overflow-y-auto">
                        <div class="space-y-3">
                            <p class="text-xs text-gray-500">Eine Kategorie schließt ihre Unterkategorien ein.</p>
                            <CategoryVisibilityFilter
                                v-model:visibility="categorySelection"
                                :categories="categories"
                                id-prefix="index-category-"
                                include-descendants
                            />
                            <div class="flex items-center gap-2">
                                <Checkbox id="index-without-category" v-model="withoutCategory" />
                                <Label for="index-without-category" class="text-sm font-normal">Ohne Kategorie</Label>
                            </div>
                            <Button v-if="selectedCategoryCount > 0" variant="ghost" size="sm" @click="resetCategoryFilter"
                                >Filter zurücksetzen</Button
                            >
                        </div>
                    </PopoverContent>
                </Popover>
                <span class="text-sm text-gray-500">{{ filteredMapPoints.length === 1 ? '1 Punkt' : `${filteredMapPoints.length} Punkte` }}</span>
                <template v-if="selectedVisibleIds.length > 0">
                    <Button variant="outline" size="sm" class="ml-auto" data-test="publish-selected" @click="setSelectedPublished(true)">
                        <Eye />
                        Veröffentlichen
                    </Button>
                    <Button variant="outline" size="sm" data-test="unpublish-selected" @click="setSelectedPublished(false)">
                        <EyeOff />
                        Nicht veröffentlichen
                    </Button>
                </template>
                <Button v-if="selectedVisibleIds.length > 0" variant="outline" size="sm" data-test="change-category" @click="openCategoryDialog">
                    <FolderInput />
                    Kategorie ändern
                </Button>
                <Button
                    v-if="selectedVisibleIds.length > 0 && characteristics.length > 0"
                    variant="outline"
                    size="sm"
                    data-test="change-characteristic"
                    @click="openCharacteristicDialog"
                >
                    <Tags />
                    Maßnahmen
                </Button>
                <Button
                    v-if="selectedVisibleIds.length > 0"
                    variant="destructive"
                    size="sm"
                    data-test="delete-selected"
                    @click="showBulkDeleteDialog = true"
                >
                    <Trash />
                    {{ selectedVisibleIds.length === 1 ? '1 Punkt löschen' : `${selectedVisibleIds.length} Punkte löschen` }}
                </Button>
            </div>
            <Table>
                <TableCaption>Liste aller Kartenpunkte</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-8">
                            <Checkbox
                                :model-value="allVisibleSelected ? true : selectedVisibleIds.length > 0 ? 'indeterminate' : false"
                                aria-label="Alle angezeigten Punkte auswählen"
                                data-test="select-all-points"
                                @update:model-value="toggleAllVisible"
                            />
                        </TableHead>
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
                            <TableCell class="w-8">
                                <Checkbox
                                    :model-value="selectedPointIds.has(point.id)"
                                    :aria-label="`${point.title} auswählen`"
                                    @update:model-value="(checked) => togglePoint(point.id, checked)"
                                />
                            </TableCell>
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
                                <div v-if="point.characteristics.length > 0" class="mt-1 flex flex-wrap gap-1">
                                    <MapPointCharacteristicBadge
                                        v-for="characteristic in point.characteristics"
                                        :key="characteristic.id"
                                        :characteristic="characteristic"
                                    />
                                </div>
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
                            <TableCell colspan="2" />
                            <TableCell colspan="8" class="whitespace-normal">
                                <div class="flex flex-wrap gap-x-12 gap-y-4 py-1">
                                    <MapPointFieldList
                                        v-if="point.fields.length > 0"
                                        :fields="point.fields"
                                        :characteristics="point.characteristics"
                                        mark-internal
                                    />
                                    <div v-if="point.former_fields.length > 0" class="space-y-1">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            Frühere Angaben (gehören nicht mehr zur Kategorie oder zu den Maßnahmen, nur hier sichtbar)
                                        </p>
                                        <MapPointFieldList :fields="point.former_fields" class="opacity-70" />
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-if="filteredMapPoints.length === 0">
                        <TableCell colspan="10" class="py-8 text-center text-gray-500"> Keine Punkte gefunden </TableCell>
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
                    <div class="space-y-2" data-test="export-category-mode">
                        <Label>Kategorien</Label>
                        <RadioGroup v-model="exportMode" class="flex flex-wrap gap-4">
                            <div class="flex items-center gap-2">
                                <RadioGroupItem id="export_mode_several" value="several" />
                                <Label for="export_mode_several" class="font-normal">Alle Kategorien</Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <RadioGroupItem id="export_mode_one" value="one" />
                                <Label for="export_mode_one" class="font-normal">Eine Kategorie mit Maßnahmen</Label>
                            </div>
                        </RadioGroup>
                        <Select v-if="exportMode === 'one'" id="export_category" v-model="exportCategoryId">
                            <SelectTrigger class="w-full" data-test="export-category">
                                <SelectValue placeholder="Kategorie wählen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="{ category, depth } in exportCategoryEntries"
                                    :key="category.id"
                                    :value="category.id"
                                    :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-gray-500">
                            {{
                                exportMode === 'one'
                                    ? 'Exportiert nur die Punkte dieser Kategorie und ihrer Unterkategorien, mit einer Spalte je Maßnahme (1 oder 0) und den Feldern der Maßnahmen.'
                                    : 'Exportiert die Punkte aller Kategorien mit ihren Zusatzfeldern, ohne Maßnahmen.'
                            }}
                        </p>
                    </div>
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
                    <Button
                        as="a"
                        :href="exportUrl"
                        :disabled="exportMode === 'one' && exportCategoryId === null"
                        :class="{ 'pointer-events-none opacity-50': exportMode === 'one' && exportCategoryId === null }"
                        @click="showExportDialog = false"
                        ><Download />Herunterladen</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="showCategoryDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >Kategorie von {{ selectedVisibleIds.length === 1 ? '1 Punkt' : `${selectedVisibleIds.length} Punkten` }} ändern</DialogTitle
                    >
                    <DialogDescription>
                        Werte von Zusatzfeldern, die die neue Kategorie nicht hat, bleiben als frühere Angaben erhalten.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <Label for="target_category">Neue Kategorie</Label>
                    <Select id="target_category" v-model="targetCategoryId">
                        <SelectTrigger class="w-full" data-test="target-category">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NO_CATEGORY">Keine Kategorie</SelectItem>
                            <SelectItem
                                v-for="{ category, depth } in targetCategoryEntries"
                                :key="category.id"
                                :value="category.id"
                                :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCategoryDialog = false">Abbrechen</Button>
                    <Button data-test="confirm-change-category" @click="moveSelectedPoints">Kategorie ändern</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="showCharacteristicDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >Maßnahme von {{ selectedVisibleIds.length === 1 ? '1 Punkt' : `${selectedVisibleIds.length} Punkten` }} ändern</DialogTitle
                    >
                    <DialogDescription>
                        Hinzufügen lässt sich eine Maßnahme nur, wenn die Kategorien aller ausgewählten Punkte sie anbieten. Werte einer entfernten
                        Maßnahme bleiben als frühere Angaben erhalten.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <Label for="target_characteristic">Maßnahme</Label>
                    <Select id="target_characteristic" v-model="targetCharacteristicId">
                        <SelectTrigger class="w-full" data-test="target-characteristic">
                            <SelectValue placeholder="Maßnahme wählen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="characteristic in targetCharacteristics" :key="characteristic.id" :value="characteristic.id">
                                {{ characteristic.name }} ({{ characteristic.category_name }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="targetCharacteristics.length === 0" class="text-sm text-gray-500">
                        Die Kategorien der ausgewählten Punkte bieten keine gemeinsame Maßnahme an.
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCharacteristicDialog = false">Abbrechen</Button>
                    <Button
                        variant="outline"
                        data-test="remove-characteristic"
                        :disabled="targetCharacteristicId === null || !removableCharacteristicIds.has(targetCharacteristicId)"
                        @click="updateSelectedCharacteristic('remove')"
                        >Entfernen</Button
                    >
                    <Button
                        data-test="add-characteristic"
                        :disabled="targetCharacteristicId === null || !addableCharacteristicIds.includes(targetCharacteristicId)"
                        @click="updateSelectedCharacteristic('add')"
                        >Hinzufügen</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="showBulkDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >{{ selectedVisibleIds.length === 1 ? '1 Punkt' : `${selectedVisibleIds.length} Punkte` }} wirklich löschen?</DialogTitle
                    >
                    <DialogDescription>Die ausgewählten Punkte werden mit ihren Zusatzfeldern unwiederbringlich gelöscht.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showBulkDeleteDialog = false">Abbrechen</Button>
                    <Button variant="destructive" data-test="confirm-delete-selected" @click="deleteSelectedPoints">Löschen</Button>
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
