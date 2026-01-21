<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

const props = defineProps<{
    open: boolean;
    user: App.Data.UserData | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const newPassword = ref('');

watch(
    () => props.open,
    (open) => {
        if (open) {
            newPassword.value = '';
        }
    },
);

function close() {
    emit('update:open', false);
}

function changePassword() {
    if (newPassword.value.length < 8) {
        toast.error('Das Passwort muss mindestens 8 Zeichen lang sein.');
        return;
    }

    if (!props.user) {
        return;
    }

    router.put(
        route('users.changePassword', props.user.id),
        { password: newPassword.value },
        {
            onSuccess: () => {
                close();
                emit('success');
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Passwort ändern</DialogTitle>
                <DialogDescription v-if="user">
                    Passwort von <b>{{ user.first_name }} {{ user.last_name }}</b> ändern
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
                <Button variant="outline" @click="close">Abbrechen</Button>
                <Button @click="changePassword">Passwort ändern</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
