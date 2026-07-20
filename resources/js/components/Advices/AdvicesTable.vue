<script setup lang="ts">
import CreateAdviceDialog from '@/components/CreateAdviceDialog.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { Input } from '@/shadcn/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router } from '@inertiajs/vue3';
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
import { AlertCircle, CheckCircle2, Clock, Home, Loader2, Phone, ShoppingCart } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { isActingAsAdmin } from '../../authHelper';
import PhysicalValue from '../../views/PhysicalValue.vue';

const emit = defineEmits(['selectAdviceId']);

const props = defineProps<{
    onlyOneGroup: boolean;
    advices: App.Data.DataProtectedAdviceData[];
    groups: App.Data.GroupData[];
    adviceStatuses: { id: string; name: string }[];
    adviceTypes: { id: number; name: string }[];
    advisors: { id: string; name: string }[];
}>();

// Local mutable copy so we can update optimistically
const localAdvices = ref<App.Data.DataProtectedAdviceData[]>([...props.advices]);
watch(
    () => props.advices,
    (v) => {
        localAdvices.value = [...v];
    },
    { deep: true },
);

// Lookup data
const adviceStatusList = computed(() => props.adviceStatuses);
const adviceTypeList = computed(() => props.adviceTypes);
const advisorMap = computed(() => new Map(props.advisors.map((a) => [a.id, a.name])));

function statusName(id: string | null) {
    return adviceStatusList.value.find((s) => s.id === id)?.name ?? '-';
}

function typeName(id: number | null) {
    return adviceTypeList.value.find((t) => t.id === id)?.name ?? '-';
}

function groupName(id: string | null) {
    return props.groups.find((g) => g.id === id)?.name ?? '-';
}

function formatDate(val: string | null) {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('de-DE');
}

function getResultBadgeVariant(resultId: number) {
    switch (resultId) {
        case 0:
            return { variant: 'default' as const, icon: Clock };
        case 1:
            return { variant: 'outline' as const, icon: Loader2 };
        case 2:
            return { variant: 'outline' as const, icon: CheckCircle2, extraClass: 'border-green-300 bg-green-50 text-green-700' };
        case 3:
            return { variant: 'destructive' as const, icon: AlertCircle };
        default:
            return { variant: 'outline' as const, icon: null };
    }
}

// Authorization helpers
function userCanEdit(advice: App.Data.DataProtectedAdviceData) {
    return advice.can_edit;
}

// Inline editing state
const editingCell = ref<{ rowId: string; col: string } | null>(null);
const editValue = ref<any>(null);

function startEdit(rowId: string, col: string, currentValue: any) {
    editingCell.value = { rowId, col };
    editValue.value = currentValue;
}

function cancelEdit() {
    editingCell.value = null;
    editValue.value = null;
}

function commitEdit(adviceId: string) {
    if (!editingCell.value) return;
    const field = editingCell.value.col;

    if (field === 'advice_status_id') {
        router.put(route('advices.updateStatus', adviceId), { advice_status_id: editValue.value });
    } else if (field === 'advisor_id') {
        router.put(route('advices.updateAdvisor', adviceId), { advisor_id: editValue.value });
    }

    cancelEdit();
}

// Open advice
function openAdvice(adviceId: string) {
    router.get(route('advices.show', adviceId));
}

// Assign advice
function assignAdvice(id: string) {
    router.post(route('advices.assign', id));
}

// Table state
const sorting = ref<SortingState>([{ id: 'created_at', desc: true }]);
const columnFilters = ref<ColumnFiltersState>([]);
const globalFilter = ref('');
const showFilters = ref(false);

const columnHelper = createColumnHelper<App.Data.DataProtectedAdviceData>();

const columns = computed(() => {
    const cols = [
        columnHelper.display({
            id: 'actions',
            header: 'Öffnen',
            enableSorting: false,
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.created_at, {
            id: 'created_at',
            header: 'Erstellt am',
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.advice_status_id, {
            id: 'advice_status_id',
            header: 'Status',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.advisor_id, {
            id: 'advisor_id',
            header: 'Berater*in',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.distance, {
            id: 'distance',
            header: 'Luftlinie',
            enableSorting: true,
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.type, {
            id: 'type',
            header: 'Typ',
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.first_name, {
            id: 'first_name',
            header: 'Vorname',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.last_name, {
            id: 'last_name',
            header: 'Nachname',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.email, {
            id: 'email',
            header: 'E-Mail',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.phone, {
            id: 'phone',
            header: 'Telefon',
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.street, {
            id: 'street',
            header: 'Straße & Nr.',
            enableColumnFilter: false,
        }),
        columnHelper.accessor((row) => row.zip, {
            id: 'zip',
            header: 'PLZ',
            enableColumnFilter: true,
        }),
        columnHelper.accessor((row) => row.city, {
            id: 'city',
            header: 'Stadt',
            enableColumnFilter: true,
        }),
    ];
    return cols;
});

const table = useVueTable({
    get data() {
        return localAdvices.value;
    },
    get columns() {
        return columns.value;
    },
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
    onSortingChange: (u) => {
        sorting.value = typeof u === 'function' ? u(sorting.value) : u;
    },
    onColumnFiltersChange: (u) => {
        columnFilters.value = typeof u === 'function' ? u(columnFilters.value) : u;
    },
    onGlobalFilterChange: (u) => {
        globalFilter.value = u;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
});

const totalCount = computed(() => table.getFilteredRowModel().rows.length);
</script>

<template>
    <div class="flex h-screen flex-col">
        <h2 class="mb-4 ml-2 text-2xl font-semibold">Beratungen</h2>

        <!-- Toolbar -->
        <Card class="mx-2 mb-2 p-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Global search -->
                <Input v-model="globalFilter" placeholder="Suchen..." class="h-8 w-48" />

                <!-- Show/hide column filters -->
                <Button variant="outline" size="sm" class="h-8" @click="showFilters = !showFilters">
                    {{ showFilters ? 'Filter ausblenden' : 'Spaltenfilter' }}
                </Button>

                <!-- Create new advice -->
                <div class="ml-auto">
                    <CreateAdviceDialog :groups="props.groups" />
                </div>
            </div>
        </Card>

        <!-- Table with scrollable container -->
        <div class="mx-2 mb-0 min-h-0 flex-1 overflow-x-auto overflow-y-auto rounded-lg border border-slate-200 shadow-sm">
            <Table class="w-full">
                <TableHeader class="bg-background">
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="header.column.getCanSort() ? 'cursor-pointer whitespace-nowrap select-none' : 'whitespace-nowrap'"
                            @click="header.column.getToggleSortingHandler()?.($event)"
                        >
                            <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                            <span v-if="header.column.getIsSorted() === 'asc'"> ↑</span>
                            <span v-else-if="header.column.getIsSorted() === 'desc'"> ↓</span>
                        </TableHead>
                    </TableRow>
                    <!-- Filter row -->
                    <TableRow v-if="showFilters">
                        <TableHead v-for="header in table.getHeaderGroups()[0].headers" :key="`f-${header.id}`" class="py-1">
                            <Input
                                v-if="header.column.getCanFilter()"
                                :value="(header.column.getFilterValue() as string) ?? ''"
                                @input="(e: Event) => header.column.setFilterValue((e.target as HTMLInputElement).value)"
                                class="h-6 text-xs"
                                placeholder="Filter..."
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-for="(row, idx) in table.getRowModel().rows" :key="row.id">
                        <!-- Data row -->
                        <TableRow :class="idx % 2 === 0 ? 'bg-slate-100' : ''">
                            <!-- Actions -->
                            <TableCell class="whitespace-nowrap">
                                <Button
                                    v-if="userCanEdit(row.original)"
                                    variant="outline"
                                    size="sm"
                                    class="h-7 text-xs"
                                    @click="openAdvice(row.original.id)"
                                    >Öffnen</Button
                                >
                            </TableCell>

                            <!-- Group -->
                            <TableCell v-if="!onlyOneGroup">{{ groupName(row.original.group_id) }}</TableCell>

                            <!-- Created at -->
                            <TableCell class="whitespace-nowrap">{{ formatDate(row.original.created_at) }}</TableCell>

                            <!-- Status (editable) -->
                            <TableCell>
                                <template v-if="editingCell?.rowId === row.id && editingCell?.col === 'advice_status_id'">
                                    <Select
                                        :model-value="editValue"
                                        @update:model-value="
                                            (v) => {
                                                editValue = v;
                                                commitEdit(row.original.id);
                                            }
                                        "
                                    >
                                        <SelectTrigger class="h-7 w-40 text-xs" @keydown.escape="cancelEdit">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="s in adviceStatusList" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </template>
                                <template v-else>
                                    <div
                                        v-if="userCanEdit(row.original)"
                                        data-test="edit-status"
                                        class="cursor-pointer"
                                        @click="startEdit(row.id, 'advice_status_id', row.original.advice_status_id)"
                                    >
                                        <Badge
                                            :variant="getResultBadgeVariant(row.original.result).variant"
                                            :class="getResultBadgeVariant(row.original.result).extraClass"
                                        >
                                            <component :is="getResultBadgeVariant(row.original.result).icon" class="mr-1 h-3 w-3" />
                                            {{ statusName(row.original.advice_status_id) }}
                                        </Badge>
                                    </div>
                                    <Badge
                                        v-else
                                        :variant="getResultBadgeVariant(row.original.result).variant"
                                        :class="getResultBadgeVariant(row.original.result).extraClass"
                                    >
                                        <component :is="getResultBadgeVariant(row.original.result).icon" class="mr-1 h-3 w-3" />
                                        {{ statusName(row.original.advice_status_id) }}
                                    </Badge>
                                </template>
                            </TableCell>

                            <!-- Advisor -->
                            <TableCell>
                                <template v-if="isActingAsAdmin">
                                    <template v-if="editingCell?.rowId === row.id && editingCell?.col === 'advisor_id'">
                                        <Select
                                            :model-value="editValue !== null ? String(editValue) : undefined"
                                            @update:model-value="
                                                (v) => {
                                                    editValue = v ? String(v) : null;
                                                    commitEdit(row.original.id);
                                                }
                                            "
                                        >
                                            <SelectTrigger class="h-7 w-44 text-xs" @keydown.escape="cancelEdit">
                                                <SelectValue placeholder="Auswählen..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="a in props.advisors" :key="a.id" :value="String(a.id)">{{ a.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>
                                    <span
                                        v-else
                                        data-test="edit-advisor"
                                        class="cursor-pointer rounded px-1 py-0.5 text-sm hover:bg-accent"
                                        @click="startEdit(row.id, 'advisor_id', row.original.advisor_id)"
                                        >{{ advisorMap.get(row.original.advisor_id ?? '') ?? '-' }}</span
                                    >
                                </template>
                                <template v-else>
                                    <span v-if="row.original.advisor_id !== null" class="text-sm">
                                        {{ advisorMap.get(row.original.advisor_id) ?? '-' }}
                                    </span>
                                    <Button v-else variant="default" size="sm" class="h-7 text-xs" @click="assignAdvice(row.original.id)">
                                        Übernehmen
                                    </Button>
                                </template>
                            </TableCell>

                            <!-- Distance -->
                            <TableCell>
                                <PhysicalValue :value="row.original.distance" unit="m" />
                            </TableCell>

                            <!-- Type icon -->
                            <TableCell>
                                <Home v-if="row.original.type === 0" class="h-4 w-4" />
                                <Phone v-else-if="row.original.type === 1" class="h-4 w-4" />
                                <ShoppingCart v-else-if="row.original.type === 2" class="h-4 w-4" />
                                <span v-else class="text-xs text-muted-foreground">??</span>
                            </TableCell>

                            <!-- Name fields -->
                            <TableCell class="text-sm">{{ row.original.first_name }}</TableCell>
                            <TableCell class="text-sm">{{ row.original.last_name }}</TableCell>

                            <!-- Email -->
                            <TableCell class="text-sm">
                                <span v-if="row.original.email !== null">{{ row.original.email }}</span>
                                <span v-else class="text-muted-foreground italic">verborgen</span>
                            </TableCell>

                            <!-- Phone -->
                            <TableCell class="text-sm">
                                <span v-if="row.original.phone !== null">{{ row.original.phone }}</span>
                                <span v-else class="text-muted-foreground italic">verborgen</span>
                            </TableCell>

                            <!-- Street -->
                            <TableCell class="text-sm">{{ row.original.street }} {{ row.original.street_number }}</TableCell>

                            <!-- Zip / City -->
                            <TableCell class="text-sm">{{ row.original.zip }}</TableCell>
                            <TableCell class="text-sm">{{ row.original.city }}</TableCell>
                        </TableRow>
                    </template>

                    <TableEmpty v-if="table.getRowModel().rows.length === 0" :colspan="table.getAllLeafColumns().length">
                        Keine Beratungen gefunden.
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <!-- Summary footer -->
        <div class="px-4 pb-6 text-sm text-slate-600">{{ totalCount }} Beratung{{ totalCount !== 1 ? 'en' : '' }}</div>
    </div>
</template>

<style scoped>
@media screen and (min-width: 680px) {
    .main-table {
        margin: 10px 0 0 0;
    }
}
</style>
