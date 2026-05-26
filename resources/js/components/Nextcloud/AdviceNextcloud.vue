<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertTriangle, FolderOpen, FolderPlus, Link2, Link2Off } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import NextcloudCreateFolderDialog from './NextcloudCreateFolderDialog.vue';
import NextcloudFileList from './NextcloudFileList.vue';
import NextcloudLinkFolderDialog from './NextcloudLinkFolderDialog.vue';

type Item = App.Nextcloud.Data.NextcloudFile | App.Nextcloud.Data.NextcloudDir;

const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
}>();

const showCreateDialog = ref(false);
const showLinkDialog = ref(false);
const unlinking = ref(false);

const files = ref<Item[] | null>(null);
const loadingFiles = ref(false);
const filesBroken = ref(false);

async function loadFiles() {
    if (!props.advice.nextcloud_folder_id) return;
    loadingFiles.value = true;
    filesBroken.value = false;
    try {
        const { data } = await axios.get(`/api/advices/${props.advice.id}/nextcloud/files`);
        files.value = data;
    } catch {
        filesBroken.value = true;
        files.value = null;
    } finally {
        loadingFiles.value = false;
    }
}

watch(
    () => props.advice.nextcloud_folder_id,
    (folderId) => {
        if (folderId) {
            loadFiles();
        } else {
            files.value = null;
            filesBroken.value = false;
        }
    },
    { immediate: true },
);

const state = computed<'loading' | 'unlinked' | 'linked' | 'broken'>(() => {
    if (!props.advice.nextcloud_folder_id) return 'unlinked';
    if (loadingFiles.value) return 'loading';
    if (filesBroken.value) return 'broken';
    return 'linked';
});

const defaultFolderName = computed(() => {
    return props.advice.first_name.slice(0, 2) +
    props.advice.last_name.slice(0, 2);
});

async function unlink() {
    unlinking.value = true;
    try {
        await axios.delete(`/api/advices/${props.advice.id}/nextcloud/link`);
        router.reload({ only: ['advice'] });
    } finally {
        unlinking.value = false;
    }
}

function refresh() {
    files.value = null;
    loadFiles();
    router.reload({ only: ['advice'] });
}
</script>

<template>
    <div>
        <!-- Loading -->
        <div v-if="state === 'loading'" class="px-4 py-2 text-sm text-gray-400">Dateien werden geladen…</div>

        <!-- Unlinked -->
        <div v-else-if="state === 'unlinked'" class="flex gap-2 px-4 py-2">
            <Button variant="outline" size="sm" @click="showCreateDialog = true">
                <FolderPlus class="mr-2 h-4 w-4" />
                Ordner anlegen
            </Button>
            <Button variant="outline" size="sm" @click="showLinkDialog = true">
                <Link2 class="mr-2 h-4 w-4" />
                Ordner verknüpfen
            </Button>
        </div>

        <!-- Linked -->
        <div v-else-if="state === 'linked'">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <FolderOpen class="h-4 w-4 text-blue-500" />
                    <span class="max-w-64 truncate text-xs">{{ advice.nextcloud_folder_path }}</span>
                </div>
                <Button variant="ghost" size="sm" :disabled="unlinking" @click="unlink">
                    <Link2Off class="mr-1 h-4 w-4 text-gray-400" />
                    Verknüpfung aufheben
                </Button>
            </div>
            <NextcloudFileList :items="files ?? []" :advice-id="advice.id" @uploaded="loadFiles" />
        </div>

        <!-- Broken link -->
        <div v-else-if="state === 'broken'" class="space-y-2 px-4 py-3">
            <div class="flex items-center gap-2 text-sm text-amber-600">
                <AlertTriangle class="h-4 w-4 shrink-0" />
                <span>Nextcloud-Ordner nicht gefunden. Möglicherweise wurde er verschoben oder gelöscht.</span>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="showLinkDialog = true">
                    <Link2 class="mr-2 h-4 w-4" />
                    Neu verknüpfen
                </Button>
                <Button variant="ghost" size="sm" :disabled="unlinking" @click="unlink">
                    <Link2Off class="mr-2 h-4 w-4" />
                    Verknüpfung aufheben
                </Button>
            </div>
        </div>

        <!-- Dialogs -->
        <NextcloudCreateFolderDialog v-model:open="showCreateDialog" :advice-id="advice.id" :default-name="defaultFolderName" />
        <NextcloudLinkFolderDialog v-model:open="showLinkDialog" :advice-id="advice.id" :search-slug="defaultFolderName" />
    </div>
</template>
