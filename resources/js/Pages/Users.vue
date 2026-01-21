<script setup lang="ts">
import ChangePasswordDialog from '@/components/Users/ChangePasswordDialog.vue';
import UserFormDialog from '@/components/Users/UserFormDialog.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, Key, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { CustomPageProps } from '@/types/pageProps';

const props = defineProps<{
    canPromoteUsersToSystemAdmin: boolean;
    users: App.Data.UserData[];
}>();

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);

// Dialog states
const createEditDialogOpen = ref(false);
const passwordDialogOpen = ref(false);
const editingUser = ref<App.Data.UserData | null>(null);
const passwordUser = ref<App.Data.UserData | null>(null);

// Open create dialog
function openCreateDialog() {
    editingUser.value = null;
    createEditDialogOpen.value = true;
}

// Open edit dialog
function openEditDialog(user: App.Data.UserData) {
    editingUser.value = user;
    createEditDialogOpen.value = true;
}

// Open password dialog
function openPasswordDialog(user: App.Data.UserData) {
    passwordUser.value = user;
    passwordDialogOpen.value = true;
}

// Handle success from dialogs
function handleUserFormSuccess() {
    router.reload();
}

function handlePasswordChangeSuccess() {
    router.reload();
}
</script>

<template>
    <div class="container mx-auto py-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Berater*innen</h1>
                    <p class="mt-2 text-gray-600">Verwalte Berater*innen</p>
                </div>

                <Button @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Neue*n Berater*in anlegen
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vorname</TableHead>
                                <TableHead>Nachname</TableHead>
                                <TableHead>E-Mail Adresse</TableHead>
                                <TableHead>Gruppen</TableHead>
                                <TableHead v-if="canPromoteUsersToSystemAdmin">System Admin</TableHead>
                                <TableHead class="text-right">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in props.users" :key="user.id">
                                <TableCell class="font-medium">{{ user.first_name }}</TableCell>
                                <TableCell>{{ user.last_name }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-2">
                                        <div
                                            v-for="group in user.groups"
                                            :key="group.id"
                                            class="flex items-center gap-2"
                                        >
                                            <img
                                                v-if="group.logo_path"
                                                :src="group.logo_path"
                                                :alt="group.name"
                                                class="h-6 w-6 rounded object-cover"
                                            />
                                            <span class="text-sm">{{ group.name }}</span>
                                        </div>
                                        <span v-if="user.groups.length === 0" class="text-sm text-gray-400 italic">
                                            Keine Gruppen
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell v-if="canPromoteUsersToSystemAdmin">
                                    <span
                                        :class="{
                                            'rounded px-2 py-1 text-xs font-medium': true,
                                            'bg-green-100 text-green-800': user.is_admin,
                                            'bg-gray-100 text-gray-800': !user.is_admin,
                                        }"
                                    >
                                        {{ user.is_admin ? 'Ja' : 'Nein' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openEditDialog(user)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="outline" size="sm" @click="openPasswordDialog(user)">
                                            <Key class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.users.length === 0">
                                <TableCell :colspan="canPromoteUsersToSystemAdmin ? 6 : 5" class="py-8 text-center text-gray-500">
                                    Noch keine Berater*innen erstellt
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Create/Edit User Dialog -->
    <UserFormDialog
        v-model:open="createEditDialogOpen"
        :user="editingUser"
        :can-promote-users-to-system-admin="canPromoteUsersToSystemAdmin"
        :current-group="currentGroup"
        @success="handleUserFormSuccess"
    />

    <!-- Change Password Dialog -->
    <ChangePasswordDialog v-model:open="passwordDialogOpen" :user="passwordUser" @success="handlePasswordChangeSuccess" />
</template>
