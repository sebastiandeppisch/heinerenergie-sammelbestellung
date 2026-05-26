<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';
import NextcloudDirBrowser from './NextcloudDirBrowser.vue';

const props = defineProps<{
    adviceId: string;
    defaultName: string;
    initialPath?: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const name = ref(props.defaultName);
const parentPath = ref(props.initialPath ?? '/');
const loading = ref(false);
const error = ref<string | null>(null);

watch(open, (val) => {
    if (val) {
        name.value = props.defaultName;
        parentPath.value = props.initialPath ?? '/';
        error.value = null;
    }
});

async function submit() {
    if (!name.value.trim()) return;
    loading.value = true;
    error.value = null;

    try {
        await axios.post(`/api/advices/${props.adviceId}/nextcloud/folder`, {
            name: name.value.trim(),
            parent_path: parentPath.value,
        });
        open.value = false;
        router.reload({ only: ['advice'] });
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Fehler beim Anlegen des Ordners.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Nextcloud-Ordner anlegen</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <label for="nextcloud-folder-name" class="text-sm font-medium">Ordnername</label>
                    <input
                        id="nextcloud-folder-name"
                        v-model="name"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        @keydown.enter="submit"
                    />
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm font-medium">Speicherort</label>
                    <NextcloudDirBrowser v-if="open" v-model:selected-path="parentPath" :advice-id="adviceId" :initial-path="initialPath ?? '/'" />
                </div>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="open = false">Abbrechen</Button>
                <Button :disabled="loading || !name.trim()" @click="submit">
                    {{ loading ? 'Wird angelegt…' : 'Anlegen' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
