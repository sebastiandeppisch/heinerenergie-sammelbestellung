<script setup lang="ts">
import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import CoordinateFieldPreview from '@/components/FormSubmissions/FieldPreview/CoordinateFieldPreview.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/shadcn/components/ui/popover';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { TooltipProvider } from '@/shadcn/components/ui/tooltip';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnFiltersState,
    type SortingState,
} from '@tanstack/vue-table';
import { ChevronDown, ChevronUp, Filter } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

type MapPointData = App.Data.MapPointData;
type MapPointCategoryData = App.Data.MapPointCategoryData;
type Coordinate = App.ValueObjects.Coordinate;

interface MapPointRow {
    id: string;
    title: string;
    description: string;
    location: string;
    coordinate: Coordinate;
    categoryId: string | null;
    categoryName: string;
    categoryImagePath: string | null;
}

const props = defineProps<{
    pointsByCategory: Record<string, Array<MapPointData>>;
    categories: Array<MapPointCategoryData>;
}>();

function categoryById(categoryId: string | null): MapPointCategoryData | undefined {
    return props.categories.find((category) => category.id === categoryId);
}

const rows = computed<Array<MapPointRow>>(() =>
    Object.values(props.pointsByCategory)
        .flat()
        .map((point) => {
            const category = categoryById(point.category_id);
            return {
                id: point.id,
                title: point.title,
                description: point.description,
                location: point.location ?? '',
                coordinate: point.coordinate,
                categoryId: point.category_id,
                categoryName: category?.name ?? '',
                categoryImagePath: category?.image_path ?? null,
            };
        }),
);

const categoryVisibility = reactive<Record<string, boolean>>(Object.fromEntries(props.categories.map((category) => [category.id, true])));

const globalFilter = ref('');
const columnFilters = ref<ColumnFiltersState>([]);
const sorting = ref<SortingState>([]);
const showFilters = ref(false);

const columnHelper = createColumnHelper<MapPointRow>();

const columns = [
    columnHelper.accessor((row) => row.categoryName, {
        id: 'category',
        header: 'Kategorie',
        filterFn: (row, _columnId, filterValue: Array<string>) => filterValue.includes(row.original.categoryId ?? ''),
    }),
    columnHelper.accessor((row) => row.title, {
        id: 'title',
        header: 'Name',
        enableColumnFilter: true,
    }),
    columnHelper.accessor((row) => row.description, {
        id: 'description',
        header: 'Beschreibung',
        enableColumnFilter: true,
    }),
    columnHelper.accessor((row) => row.location, {
        id: 'location',
        header: 'Ort',
        enableColumnFilter: true,
    }),
];

const table = useVueTable({
    get data() {
        return rows.value;
    },
    columns,
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get globalFilter() {
            return globalFilter.value;
        },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onColumnFiltersChange: (updater) => {
        columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater;
    },
    onGlobalFilterChange: (value) => {
        globalFilter.value = value;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
});

watch(
    categoryVisibility,
    () => {
        table.getColumn('category')?.setFilterValue(
            Object.entries(categoryVisibility)
                .filter(([, visible]) => visible)
                .map(([categoryId]) => categoryId),
        );
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <TooltipProvider>
        <Card>
            <CardContent class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Input v-model="globalFilter" placeholder="Suchen..." class="h-8 w-48" />
                    <Button type="button" variant="outline" size="sm" class="h-8" @click="showFilters = !showFilters">
                        {{ showFilters ? 'Suche pro Spalte ausblenden' : 'Suche pro Spalte' }}
                        <ChevronUp v-if="showFilters" class="h-4 w-4" />
                        <ChevronDown v-else class="h-4 w-4" />
                    </Button>
                </div>

                <div class="overflow-x-auto rounded-lg border">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead
                                    v-for="header in headerGroup.headers"
                                    :key="header.id"
                                    :class="header.column.getCanSort() ? 'cursor-pointer whitespace-nowrap select-none' : 'whitespace-nowrap'"
                                    @click="header.column.getToggleSortingHandler()?.($event)"
                                >
                                    <div class="flex items-center gap-1">
                                        <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                        <span v-if="header.column.getIsSorted() === 'asc'">↑</span>
                                        <span v-else-if="header.column.getIsSorted() === 'desc'">↓</span>

                                        <Popover v-if="header.column.id === 'category' && categories.length > 1">
                                            <PopoverTrigger as-child>
                                                <Button type="button" variant="ghost" size="icon" class="h-6 w-6" @click.stop>
                                                    <Filter class="h-3.5 w-3.5" />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-64" @click.stop>
                                                <CategoryVisibilityFilter
                                                    v-model:visibility="categoryVisibility"
                                                    :categories="categories"
                                                    id-prefix="table-category-"
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                </TableHead>
                            </TableRow>
                            <TableRow v-if="showFilters">
                                <TableHead v-for="header in table.getHeaderGroups()[0].headers" :key="`f-${header.id}`" class="py-1">
                                    <Input
                                        v-if="header.column.getCanFilter() && header.column.id !== 'category'"
                                        :value="(header.column.getFilterValue() as string) ?? ''"
                                        @input="(e: Event) => header.column.setFilterValue((e.target as HTMLInputElement).value)"
                                        class="h-6 text-xs"
                                        placeholder="Filter..."
                                    />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div v-if="row.original.categoryImagePath" class="h-6 w-6 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                                            <img
                                                :src="row.original.categoryImagePath"
                                                :alt="row.original.categoryName"
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <span>{{ row.original.categoryName }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="font-medium">{{ row.original.title }}</TableCell>
                                <TableCell class="max-w-md truncate">{{ row.original.description }}</TableCell>
                                <TableCell>
                                    <CoordinateFieldPreview :value="row.original.coordinate" :label="row.original.location || undefined" />
                                </TableCell>
                            </TableRow>

                            <TableEmpty v-if="table.getRowModel().rows.length === 0" :colspan="4"> Keine Punkte gefunden. </TableEmpty>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </TooltipProvider>
</template>
