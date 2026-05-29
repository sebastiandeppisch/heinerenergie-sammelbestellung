<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import axios from 'axios';
import { Download, File, Folder, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

type Item = App.Nextcloud.Data.NextcloudFile | App.Nextcloud.Data.NextcloudDir;

const props = defineProps<{
    items: Item[];
    adviceId: string;
}>();

const emit = defineEmits<{ uploaded: [] }>();

const uploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function isFile(item: Item): item is App.Nextcloud.Data.NextcloudFile {
    return 'size' in item;
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function downloadUrl(path: string): string {
    return `/api/advices/${props.adviceId}/nextcloud/download?path=${encodeURIComponent(path)}`;
}

async function handleUpload(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        await axios.post(`/api/advices/${props.adviceId}/nextcloud/upload`, formData);
        emit('uploaded');
    } finally {
        uploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
}
</script>

<template>
    <div>
        <div v-if="items.length === 0" class="px-4 py-2 text-sm text-gray-500">Keine Dateien vorhanden.</div>

        <ul v-else class="divide-y divide-gray-100">
            <li v-for="item in items" :key="item.fileId" class="flex items-center gap-3 px-4 py-2 text-sm">
                <File v-if="isFile(item)" class="h-4 w-4 shrink-0 text-gray-400" />
                <Folder v-else class="h-4 w-4 shrink-0 text-blue-400" />

                <span class="flex-1 truncate">{{ item.name }}</span>

                <span v-if="isFile(item)" class="shrink-0 text-xs text-gray-400">
                    {{ formatSize(item.size) }}
                </span>

                <a v-if="isFile(item)" :href="downloadUrl(item.path)" target="_blank">
                    <Button variant="ghost" size="sm">
                        <Download class="h-4 w-4" />
                    </Button>
                </a>
            </li>
        </ul>

        <div class="border-t border-gray-100 px-4 py-3">
            <input ref="fileInput" type="file" class="hidden" @change="handleUpload" />
            <Button variant="outline" size="sm" :disabled="uploading" @click="fileInput?.click()">
                <Upload class="mr-2 h-4 w-4" />
                {{ uploading ? 'Wird hochgeladen…' : 'Datei hochladen' }}
            </Button>
        </div>
    </div>
</template>
