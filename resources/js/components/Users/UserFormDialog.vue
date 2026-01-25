<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const props = defineProps<{
    open: boolean;
    user?: App.Data.UserData | null;
    canPromoteUsersToSystemAdmin: boolean;
    currentGroup?: App.Data.GroupBaseData | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isEditMode = computed(() => props.user !== null && props.user !== undefined);

const formData = ref({
    first_name: '',
    last_name: '',
    email: '',
    is_admin: false,
});

watch(
    () => props.user,
    (user) => {
        if (user) {
            formData.value = {
                first_name: user.first_name,
                last_name: user.last_name,
                email: user.email,
                is_admin: user.is_admin,
            };
        } else {
            formData.value = {
                first_name: '',
                last_name: '',
                email: '',
                is_admin: false,
            };
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (open) => {
        if (!open && !isEditMode.value) {
            formData.value = {
                first_name: '',
                last_name: '',
                email: '',
                is_admin: false,
            };
        }
    },
);

function close() {
    emit('update:open', false);
}

function save() {
    if (isEditMode.value && props.user) {
        router.put(route('users.update', props.user.id), formData.value, {
            onSuccess: () => {
                close();
                emit('success');
            },
            onError: (errors) => {
                Object.values(errors)
                    .flat()
                    .forEach((message: any) => {
                        toast.error(message);
                    });
            },
        });
    } else {
        router.post(route('users.store'), formData.value, {
            onSuccess: () => {
                close();
                emit('success');
            },
            onError: (errors) => {
                Object.values(errors)
                    .flat()
                    .forEach((message: any) => {
                        toast.error(message);
                    });
            },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
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
                    <Label for="is_admin" class="cursor-pointer text-sm font-normal">System Admin</Label>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close">Abbrechen</Button>
                <Button @click="save">{{ isEditMode ? 'Speichern' : 'Erstellen' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
