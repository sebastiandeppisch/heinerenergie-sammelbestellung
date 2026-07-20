<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Switch } from '@/shadcn/components/ui/switch';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { FlexRender, createColumnHelper, getCoreRowModel, getSortedRowModel, useVueTable, type SortingState } from '@tanstack/vue-table';
import axios from 'axios';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

type AdviceStatus = App.Data.AdviceStatusData;

const props = defineProps<{
    group: App.Data.GroupData;
    groups: App.Data.GroupData[];
}>();

const adviceStatusResult = [
    { id: 0, name: 'Neu' },
    { id: 1, name: 'In Bearbeitung' },
    { id: 2, name: 'Erfolgreich beraten' },
    { id: 3, name: 'Nicht erfolgreich' },
];

const endpoint = computed(() => `/api/groups/${props.group.id}/advicestatus`);

// The endpoint returns the full group hierarchy in one call; "own" statuses are
// derived client-side, so a single fetch feeds both tables below.
const items = ref<AdviceStatus[]>([]);
const loading = ref(false);

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get<AdviceStatus[]>(endpoint.value);
        items.value = data;
        // Reset the visibility draft to the freshly loaded (persisted) state.
        visibilityDraft.value = Object.fromEntries(data.map((s) => [s.id, s.visible_in_group]));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

watch(
    () => props.group.id,
    () => {
        items.value = [];
        load();
    },
);

// Own status items (only this group's own statuses)
const ownItems = computed(() => items.value.filter((s) => s.group_id === props.group.id));

// All items (including inherited from parent groups)
const allItems = computed(() => items.value);

// Dialog states for editing
const editDialogOpen = ref(false);
const editingId = ref<string | 'new' | null>(null);
const editForm = ref<Partial<AdviceStatus>>({});
const isSaving = ref(false);

function closeDialog() {
    editDialogOpen.value = false;
    editingId.value = null;
    editForm.value = {};
}

function startEdit(row: AdviceStatus) {
    editingId.value = row.id;
    editForm.value = { ...row };
    editDialogOpen.value = true;
}

function startNew() {
    editingId.value = 'new';
    editForm.value = { name: '', group_id: props.group.id };
    editDialogOpen.value = true;
}

async function saveEdit() {
    if (!editForm.value.name?.trim()) {
        toast.error('Name ist erforderlich');
        return;
    }
    if (editForm.value.result === undefined) {
        toast.error('Ergebnis ist erforderlich');
        return;
    }

    isSaving.value = true;
    try {
        if (editingId.value === 'new') {
            await axios.post(endpoint.value, { name: editForm.value.name, result: editForm.value.result });
        } else if (editingId.value !== null) {
            await axios.put(`${endpoint.value}/${editingId.value}`, { name: editForm.value.name, result: editForm.value.result });
        }
        await load();
        closeDialog();
        toast.success('Gespeichert');
    } catch (error) {
        toast.error('Fehler beim Speichern');
    } finally {
        isSaving.value = false;
    }
}

// Delete confirmation dialog
const removeDialogOpen = ref(false);
const removeId = ref<string | null>(null);

function openRemoveDialog(id: string) {
    removeId.value = id;
    removeDialogOpen.value = true;
}

async function confirmDelete() {
    if (!removeId.value) return;
    isSaving.value = true;
    try {
        await axios.delete(`${endpoint.value}/${removeId.value}`);
        await load();
        removeDialogOpen.value = false;
        removeId.value = null;
        toast.success('Gelöscht');
    } catch (error) {
        toast.error('Fehler beim Löschen');
    } finally {
        isSaving.value = false;
    }
}

// Visibility is edited as a local draft and only persisted on explicit save,
// so a misclick on a switch has no immediate effect.
const visibilityDraft = ref<Record<string, boolean>>({});
const isSavingVisibility = ref(false);

const changedVisibilityItems = computed(() =>
    items.value.filter((s) => visibilityDraft.value[s.id] !== undefined && visibilityDraft.value[s.id] !== s.visible_in_group),
);
const hasVisibilityChanges = computed(() => changedVisibilityItems.value.length > 0);

async function saveVisibility() {
    if (!hasVisibilityChanges.value) return;
    isSavingVisibility.value = true;
    try {
        await Promise.all(
            changedVisibilityItems.value.map((s) => axios.put(`${endpoint.value}/${s.id}`, { visible_in_group: visibilityDraft.value[s.id] })),
        );
        await load();
        toast.success('Sichtbarkeit gespeichert');
    } catch (error) {
        toast.error('Fehler beim Speichern der Sichtbarkeit');
    } finally {
        isSavingVisibility.value = false;
    }
}

const resultName = (id: number | null) => adviceStatusResult.find((r) => r.id === id)?.name ?? '-';
const groupName = (id: string | null) => props.groups.find((g) => g.id === id)?.name ?? '-';

// TanStack table for own statuses
const ownSorting = ref<SortingState>([{ id: 'name', desc: false }]);
const ownColumnHelper = createColumnHelper<AdviceStatus>();
const ownColumns = [
    ownColumnHelper.accessor('name', { header: 'Name' }),
    ownColumnHelper.accessor('result', { header: 'Ergebnis' }),
    ownColumnHelper.display({ id: 'actions', header: '' }),
];
const ownTable = useVueTable({
    get data(): AdviceStatus[] {
        return ownItems.value;
    },
    columns: ownColumns,
    state: {
        get sorting() {
            return ownSorting.value;
        },
    },
    onSortingChange: (u) => {
        ownSorting.value = typeof u === 'function' ? u(ownSorting.value) : u;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
});

// TanStack table for all statuses (visibility toggle)
const allSorting = ref<SortingState>([{ id: 'name', desc: false }]);
const allColumnHelper = createColumnHelper<AdviceStatus>();
const allColumns = [
    allColumnHelper.accessor('name', { header: 'Name' }),
    allColumnHelper.accessor('group_id', { header: 'Initiative' }),
    allColumnHelper.accessor('visible_in_group', { header: 'Sichtbar' }),
];
const allTable = useVueTable({
    get data(): AdviceStatus[] {
        return allItems.value;
    },
    columns: allColumns,
    state: {
        get sorting() {
            return allSorting.value;
        },
    },
    onSortingChange: (u) => {
        allSorting.value = typeof u === 'function' ? u(allSorting.value) : u;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
});
</script>

<template>
    <div class="mt-4 flex flex-col gap-6">
        <!-- Own advice statuses -->
        <div class="space-y-3">
            <h3 class="font-medium">Beratungszustände</h3>
            <p class="text-sm text-gray-500">
                Du kannst hier die Beratungszustände verwalten. Die Zustände werden bei allen Unter-Gruppen zur Verfügung gestellt, können aber
                ausgeblendet werden.
            </p>
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="hg in ownTable.getHeaderGroups()" :key="hg.id">
                            <TableHead
                                v-for="h in hg.headers"
                                :key="h.id"
                                :class="h.column.getCanSort() ? 'cursor-pointer select-none' : ''"
                                @click="h.column.getToggleSortingHandler()?.($event)"
                            >
                                <FlexRender :render="h.column.columnDef.header" :props="h.getContext()" />
                                <span v-if="h.column.getIsSorted() === 'asc'"> ↑</span>
                                <span v-else-if="h.column.getIsSorted() === 'desc'"> ↓</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in ownTable.getRowModel().rows" :key="row.id">
                            <TableCell>{{ row.original.name }}</TableCell>
                            <TableCell>{{ resultName(row.original.result) }}</TableCell>
                            <TableCell class="w-20">
                                <div class="flex gap-1">
                                    <div data-test="edit-status">
                                        <Button variant="ghost" size="icon" class="h-7 w-7" @click="startEdit(row.original)"><Pencil class="h-3.5 w-3.5" /></Button>
                                    </div>
                                    <div data-test="delete-status">
                                        <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:text-destructive" @click="openRemoveDialog(row.original.id)"><Trash2 class="h-3.5 w-3.5" /></Button>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableEmpty v-if="!loading && ownTable.getRowModel().rows.length === 0" :colspan="3">
                            Keine eigenen Beratungszustände vorhanden.
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>
            <Button variant="outline" size="sm" @click="startNew"> <Plus class="mr-1 h-4 w-4" />Hinzufügen </Button>
        </div>

        <!-- All statuses with visibility toggle -->
        <div class="space-y-3">
            <h3 class="font-medium">Verwendete Beratungszustände</h3>
            <p class="text-sm text-gray-500">
                Hier kannst Du für diese Gruppe festlegen, welche Beratungszustände von den Berater:innen verwendet werden sollen.
            </p>
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="hg in allTable.getHeaderGroups()" :key="hg.id">
                            <TableHead
                                v-for="h in hg.headers"
                                :key="h.id"
                                :class="h.column.getCanSort() ? 'cursor-pointer select-none' : ''"
                                @click="h.column.getToggleSortingHandler()?.($event)"
                            >
                                <FlexRender :render="h.column.columnDef.header" :props="h.getContext()" />
                                <span v-if="h.column.getIsSorted() === 'asc'"> ↑</span>
                                <span v-else-if="h.column.getIsSorted() === 'desc'"> ↓</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in allTable.getRowModel().rows" :key="row.id">
                            <TableCell>{{ row.original.name }}</TableCell>
                            <TableCell>{{ groupName(row.original.group_id) }}</TableCell>
                            <TableCell>
                                <Switch
                                    :model-value="visibilityDraft[row.original.id]"
                                    @update:model-value="(v: boolean) => (visibilityDraft[row.original.id] = v)"
                                />
                            </TableCell>
                        </TableRow>
                        <TableEmpty v-if="!loading && allTable.getRowModel().rows.length === 0" :colspan="3">
                            Keine Beratungszustände vorhanden.
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>
            <div class="flex justify-end">
                <Button size="sm" data-test="save-visibility" :disabled="!hasVisibilityChanges || isSavingVisibility" @click="saveVisibility">
                    {{ isSavingVisibility ? 'Wird gespeichert...' : 'Speichern' }}
                </Button>
            </div>
        </div>
    </div>

    <!-- Edit Dialog -->
    <Dialog v-model:open="editDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle v-if="editingId === 'new'">Beratungszustand erstellen</DialogTitle>
                <DialogTitle v-else>Beratungszustand bearbeiten</DialogTitle>
                <DialogDescription>Gib den Namen und das Ergebnis ein.</DialogDescription>
            </DialogHeader>
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Name</label>
                    <Input v-model="editForm.name" placeholder="z.B. Ausstehend" autofocus />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Ergebnis</label>
                    <Select
                        :model-value="editForm.result !== undefined ? String(editForm.result) : undefined"
                        @update:model-value="(v: any) => (editForm.result = v ? (Number(v) as AdviceStatus['result']) : undefined)"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Ergebnis wählen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="r in adviceStatusResult" :key="r.id" :value="String(r.id)">{{ r.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex gap-2 justify-end pt-4">
                    <Button variant="outline" @click="closeDialog" :disabled="isSaving">Abbrechen</Button>
                    <Button data-test="save-status" :disabled="isSaving" @click="saveEdit">{{ isSaving ? 'Wird gespeichert...' : 'Speichern' }}</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="removeDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Beratungszustand löschen?</DialogTitle>
                <DialogDescription>Soll dieser Beratungszustand wirklich gelöscht werden? Dies kann nicht rückgängig gemacht werden.</DialogDescription>
            </DialogHeader>
            <div class="flex gap-2 justify-end pt-4">
                <Button variant="outline" @click="removeDialogOpen = false" :disabled="isSaving">Abbrechen</Button>
                <Button variant="destructive" :disabled="isSaving" data-test="confirm-delete" @click="confirmDelete">
                    {{ isSaving ? 'Wird gelöscht...' : 'Löschen' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
