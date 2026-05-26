<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { ChevronRight, Folder } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import axios from 'axios';

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
    <div class="border rounded-md overflow-hidden">
        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border-b">
            <Button variant="ghost" size="sm" :disabled="history.length === 0" class="h-6 px-2 text-xs" @click="goUp">
                ← Zurück
            </Button>
            <span class="text-xs text-gray-500 truncate flex-1">{{ currentPath }}</span>
        </div>

        <div v-if="loading" class="text-sm text-gray-400 py-3 text-center">Lädt…</div>

        <div v-else-if="items.length === 0" class="text-sm text-gray-400 py-3 text-center">
            Keine Unterordner.
        </div>

        <ul v-else class="max-h-40 overflow-y-auto divide-y divide-gray-100">
            <li
                v-for="dir in items"
                :key="dir.fileId"
                class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"
            >
                <Folder class="h-4 w-4 shrink-0 text-blue-400" />
                <span class="flex-1 truncate">{{ dir.name }}</span>
                <ChevronRight
                    class="h-4 w-4 shrink-0 text-gray-400 cursor-pointer hover:text-gray-600"
                    @click="openDir(dir)"
                />
            </li>
        </ul>
    </div>
</template>
