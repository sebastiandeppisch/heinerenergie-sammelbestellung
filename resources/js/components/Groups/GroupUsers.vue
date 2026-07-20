<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Badge } from '@/shadcn/components/ui/badge';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { router } from '@inertiajs/vue3';
import {
    createColumnHelper,
    getCoreRowModel,
    getSortedRowModel,
    useVueTable,
    type SortingState,
} from '@tanstack/vue-table';
import { Edit2, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';


interface UserOption {
    id: string;
    name: string;
    email: string;
}

type GroupData = App.Data.GroupData;

const props = defineProps<{
    group: GroupData;
    groupUsers: App.Data.GroupUserData[];
    allUsers: UserOption[];
}>();

// Dialog states
const addDialogOpen = ref(false);
const selectedUserId = ref<string | null>(null);
const editDialogOpen = ref(false);
const editUserId = ref<string | null>(null);
const editIsAdmin = ref(false);
const removeDialogOpen = ref(false);
const removeUserId = ref<string | null>(null);
const isMutating = ref(false);

// Table sorting
const sorting = ref<SortingState>([]);

function getFirstName(name: string): string {
    return name.split(' ')[0];
}

function getLastName(name: string): string {
    const parts = name.split(' ');
    return parts.length > 1 ? parts.slice(1).join(' ') : '';
}

function closeAddDialog() {
    addDialogOpen.value = false;
    selectedUserId.value = null;
}

function saveNewUser() {
    if (!selectedUserId.value) return;

    const newUserId = selectedUserId.value;
    const newUser = props.allUsers.find((u: UserOption) => u.id === newUserId);
    if (!newUser) return;

    isMutating.value = true;
    router.post(
        route('groups.users.store', props.group.id),
        { id: newUserId },
        {
            preserveUrl: true,
            onSuccess: () => {
                closeAddDialog();
            },
            onFinish: () => {
                isMutating.value = false;
                closeAddDialog();
            },
        },
    );
}

function openEditDialog(userId: string) {
    const user = props.groupUsers.find(u => u.id === userId);
    if (user) {
        editUserId.value = userId;
        editIsAdmin.value = user.is_admin;
        editDialogOpen.value = true;
    }
}

function saveEdit() {
    if (!editUserId.value) return;

    isMutating.value = true;
    router.put(
        route('groups.users.update', { group: props.group.id, user: editUserId.value }),
        { is_admin: editIsAdmin.value },
        {
            onFinish: () => {
                editDialogOpen.value = false;
                isMutating.value = false;
            },
        },
    );
}

function openRemoveDialog(userId: string) {
    removeUserId.value = userId;
    removeDialogOpen.value = true;
}

function confirmRemove() {
    if (!removeUserId.value) return;

    isMutating.value = true;
    router.delete(
        route('groups.users.destroy', { group: props.group.id, user: removeUserId.value }),
        {
            onFinish: () => {
                removeDialogOpen.value = false;
                isMutating.value = false;
            },
        },
    );
}

const columnHelper = createColumnHelper<App.Data.GroupUserData>();

const columns = [
    columnHelper.accessor('name', {
        id: 'firstName',
        header: 'Vorname',
        cell: (info) => getFirstName(info.getValue()),
    }),
    columnHelper.accessor('name', {
        id: 'lastName',
        header: 'Nachname',
        cell: (info) => getLastName(info.getValue()),
    }),
    columnHelper.accessor('is_admin', {
        header: 'Admin',
        enableSorting: true,
    }),
    columnHelper.display({
        id: 'actions',
        header: 'Bearbeiten',
    }),
];

const table = useVueTable({
    get data() {
        return props.groupUsers;
    },
    columns,
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
});

const availableUsers = computed(() =>
    props.allUsers.filter((u) => !props.groupUsers.some((gu) => gu.id === u.id)),
);
</script>

<template>
    <div class="group-users space-y-4">
        <!-- Add User Button -->
        <div>
            <Button variant="outline" size="sm" data-test="add-user" @click="addDialogOpen = true">
                <Plus class="mr-1 h-4 w-4" />
                Berater:in hinzufügen
            </Button>
        </div>


        <!-- Users Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="header.column.getCanSort() ? 'cursor-pointer select-none' : ''"
                            @click="header.column.getToggleSortingHandler()?.($event)"
                        >
                            <div class="flex items-center gap-2">
                                <span v-if="!header.isPlaceholder">{{ header.column.columnDef.header }}</span>
                                <span v-if="header.column.getIsSorted() === 'asc'">↑</span>
                                <span v-else-if="header.column.getIsSorted() === 'desc'">↓</span>
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <template v-if="cell.column.id === 'firstName'">
                                {{ getFirstName(row.original.name) }}
                            </template>
                            <template v-else-if="cell.column.id === 'lastName'">
                                {{ getLastName(row.original.name) }}
                            </template>
                            <template v-else-if="cell.column.id === 'is_admin'">
                                <Badge :variant="row.original.is_admin ? 'default' : 'secondary'">
                                    {{ row.original.is_admin ? 'Ja' : 'Nein' }}
                                </Badge>
                            </template>
                            <template v-else-if="cell.column.id === 'actions'">
                                <div class="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-0"
                                        :data-test="`edit-user-${row.original.id}`"
                                        @click="openEditDialog(row.original.id)"
                                    >
                                        <Edit2 class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                        :data-test="`remove-user-${row.original.id}`"
                                        @click="openRemoveDialog(row.original.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </template>
                        </TableCell>
                    </TableRow>

                    <TableEmpty v-if="table.getRowModel().rows.length === 0" :colspan="4">
                        Keine Berater:innen in dieser Initiative.
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>
    </div>

    <!-- Add User Dialog -->
    <Dialog v-model:open="addDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Berater:in hinzufügen</DialogTitle>
                <DialogDescription>Wähle eine Person aus, um sie zu dieser Initiative hinzuzufügen.</DialogDescription>
            </DialogHeader>
            <div class="space-y-4">
                <Select :model-value="selectedUserId ?? undefined" @update:model-value="(v: any) => (selectedUserId = v ?? null)">
                    <SelectTrigger>
                        <SelectValue placeholder="Person auswählen..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="user in availableUsers" :key="user.id" :value="user.id">
                            <div><span>{{ user.name }}</span> - {{ user.email }}</div>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <div class="flex gap-2 justify-end pt-4">
                    <Button variant="outline" @click="closeAddDialog" :disabled="isMutating">Abbrechen</Button>
                    <Button :disabled="!selectedUserId || isMutating" data-test="confirm-add" @click="saveNewUser">
                        {{ isMutating ? 'Wird hinzugefügt...' : 'Hinzufügen' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Edit User Dialog -->
    <Dialog v-model:open="editDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Berater:in bearbeiten</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <Checkbox v-model="editIsAdmin" id="admin-checkbox" />
                    <label for="admin-checkbox" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Admin-Rechte
                    </label>
                </div>
                <div class="flex gap-2 justify-end pt-4">
                    <Button variant="outline" @click="editDialogOpen = false" :disabled="isMutating">Abbrechen</Button>
                    <Button :disabled="isMutating" @click="saveEdit" data-test="save-edit">
                        {{ isMutating ? 'Wird gespeichert...' : 'Speichern' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Remove User Dialog -->
    <Dialog v-model:open="removeDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Berater:in entfernen?</DialogTitle>
                <DialogDescription>Bist du sicher, dass du diese Person aus der Initiative entfernen möchtest? Der Account bleibt erhalten, der/die Berater:in hat anschließend keinen Zugriff mehr auf die Initiative. </DialogDescription>
            </DialogHeader>
            <div class="flex gap-2 justify-end pt-4">
                <Button variant="outline" @click="removeDialogOpen = false" :disabled="isMutating">Abbrechen</Button>
                <Button variant="destructive" :disabled="isMutating" @click="confirmRemove" data-test="confirm-remove">
                    {{ isMutating ? 'Wird entfernt...' : 'Entfernen' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
