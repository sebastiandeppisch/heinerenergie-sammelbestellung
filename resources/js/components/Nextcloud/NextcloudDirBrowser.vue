<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import axios from 'axios';
import { ChevronRight, Folder } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    adviceId: string;
    initialPath?: string;
}>();

const selectedPath = defineModel<string>('selectedPath', { required: true });

const items = ref<App.Nextcloud.Data.NextcloudDir[]>([]);
const currentPath = ref(props.initialPath ?? '/');
const loading = ref(false);
const history = ref<string[]>([]);

async function browse(path: string) {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/advices/${props.adviceId}/nextcloud/browse`, {
            params: { path },
        });
        items.value = data.items.filter((item: any) => !('size' in item));
        currentPath.value = data.path;
        selectedPath.value = data.path;
    } finally {
        loading.value = false;
    }
}

function openDir(dir: App.Nextcloud.Data.NextcloudDir) {
    history.value.push(currentPath.value);
    browse(dir.path);
}

function goUp() {
    const prev = history.value.pop();
    if (prev !== undefined) browse(prev);
}

onMounted(() => browse(currentPath.value));
</script>

<template>
    <div class="overflow-hidden rounded-md border">
        <div class="flex items-center gap-2 border-b bg-gray-50 px-3 py-2">
            <Button variant="ghost" size="sm" :disabled="history.length === 0" class="h-6 px-2 text-xs" @click="goUp"> ← Zurück </Button>
            <span class="flex-1 truncate text-xs text-gray-500">{{ currentPath }}</span>
        </div>

        <div v-if="loading" class="py-3 text-center text-sm text-gray-400">Lädt…</div>

        <div v-else-if="items.length === 0" class="py-3 text-center text-sm text-gray-400">Keine Unterordner.</div>

        <ul v-else class="max-h-40 divide-y divide-gray-100 overflow-y-auto">
            <li v-for="dir in items" :key="dir.fileId" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                <Folder class="h-4 w-4 shrink-0 text-blue-400" />
                <span class="flex-1 truncate">{{ dir.name }}</span>
                <ChevronRight class="h-4 w-4 shrink-0 cursor-pointer text-gray-400 hover:text-gray-600" @click="openDir(dir)" />
            </li>
        </ul>
    </div>
</template>
