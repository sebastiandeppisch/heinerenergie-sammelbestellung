<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, Key, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const props = defineProps<{
    canPromoteUsersToSystemAdmin: boolean;
    users: App.Data.UserData[];
}>();

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
    defaultLogo?: string;
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);

// Dialog states
const createEditDialogOpen = ref(false);
const passwordDialogOpen = ref(false);
const isEditMode = ref(false);
const editingUser = ref<App.Data.UserData | null>(null);

// Form data
const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    is_admin: false,
});

const newPassword = ref('');
const selectedUser = ref<null | { id: string; first_name: string; last_name: string }>(null);


// Open create dialog
function openCreateDialog() {
    isEditMode.value = false;
    editingUser.value = null;
    formData.value = {
        first_name: '',
        last_name: '',
        email: '',
        is_admin: false,
    };
    createEditDialogOpen.value = true;
}

// Open edit dialog
function openEditDialog(user: App.Data.UserData) {
    isEditMode.value = true;
    editingUser.value = user;
    formData.value = {
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
        is_admin: user.is_admin,
    };
    createEditDialogOpen.value = true;
}

// Save user (create or update)
function saveUser() {
    if (isEditMode.value && editingUser.value) {
        router.put(
            route('users.update', editingUser.value.id),
            formData.value,
            {
                onSuccess: () => {
                    createEditDialogOpen.value = false;
                    router.reload();
                },
                onError: (errors) => {
                    Object.values(errors).flat().forEach((message: any) => {
                        toast.error(message);
                    });
                },
            },
        );
    } else {
        router.post(
            route('users.store'),
            formData.value,
            {
                onSuccess: () => {
                    createEditDialogOpen.value = false;
                    router.reload();
                },
                onError: (errors) => {
                    Object.values(errors).flat().forEach((message: any) => {
                        toast.error(message);
                    });
                },
            },
        );
    }
}

// Open password dialog
function openPasswordDialog(user: App.Data.UserData) {
    selectedUser.value = user;
    newPassword.value = '';
    passwordDialogOpen.value = true;
}

// Change password
function changePassword() {
    if (newPassword.value.length < 8) {
        toast.error('Das Passwort muss mindestens 8 Zeichen lang sein.');
        return;
    }
    router.put(
        route('users.changePassword', selectedUser.value?.id),
        { password: newPassword.value },
        {
            onSuccess: () => {
                passwordDialogOpen.value = false;
                newPassword.value = '';
            },
        },
    );
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
    <Dialog v-model:open="createEditDialogOpen">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEditMode ? 'Berater*in bearbeiten' : 'Neue*n Berater*in anlegen' }}</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div v-if="!isEditMode && currentGroup" class="space-y-2">
                    <Label>Initiative</Label>
                    <div class="flex items-center gap-3 rounded-md border border-input bg-muted/50 px-3 py-2">
                        <img
                            v-if="currentGroup.logo_path"
                            :src="currentGroup.logo_path"
                            :alt="currentGroup.name"
                            class="h-8 w-8 rounded object-cover"
                        />
                        <span class="text-sm font-medium">{{ currentGroup.name }}</span>
                    </div>
                    <p class="text-xs text-gray-500">Der:die Berater:in wird dieser Initiative zugewiesen.</p>
                </div>

                <div class="space-y-2">
                    <Label for="first_name">Vorname *</Label>
                    <Input id="first_name" v-model="formData.first_name" placeholder="Vorname" />
                </div>

                <div class="space-y-2">
                    <Label for="last_name">Nachname *</Label>
                    <Input id="last_name" v-model="formData.last_name" placeholder="Nachname" />
                </div>

                <div class="space-y-2">
                    <Label for="email">E-Mail Adresse *</Label>
                    <Input id="email" v-model="formData.email" type="email" placeholder="email@example.com" />
                </div>

                <div v-if="canPromoteUsersToSystemAdmin" class="flex items-center space-x-2">
                    <Checkbox id="is_admin" v-model="formData.is_admin" />
                    <Label for="is_admin" class="text-sm font-normal cursor-pointer">System Admin</Label>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="createEditDialogOpen = false">Abbrechen</Button>
                <Button @click="saveUser">{{ isEditMode ? 'Speichern' : 'Erstellen' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Change Password Dialog -->
    <Dialog v-model:open="passwordDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Passwort ändern</DialogTitle>
                <DialogDescription>
                    Passwort von <b>{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}</b> ändern
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="newPassword">Neues Passwort</Label>
                    <Input id="newPassword" v-model="newPassword" type="password" placeholder="Neues Passwort" />
                </div>
                <p class="text-sm text-gray-600">Das Passwort muss mindestens 8 Zeichen lang sein.</p>
                <p class="text-sm text-gray-600">Der Benutzer wird per E-Mail über die Passwortänderung informiert.</p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="passwordDialogOpen = false">Abbrechen</Button>
                <Button @click="changePassword">Passwort ändern</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
