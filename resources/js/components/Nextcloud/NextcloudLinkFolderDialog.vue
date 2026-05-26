<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { ChevronRight, Folder, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    adviceId: string;
    searchSlug: string;
}>();

const open = defineModel<boolean>('open', { required: true });

// Search tab
const searchQuery = ref(props.searchSlug);
const searchResults = ref<App.Nextcloud.Data.NextcloudDir[]>([]);
const searching = ref(false);

// Browse tab
const browseItems = ref<App.Nextcloud.Data.NextcloudDir[]>([]);
const browsePath = ref('/');
const browsing = ref(false);
const browseHistory = ref<string[]>([]);

const linking = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(search, 300);
});

async function search() {
    searching.value = true;
    try {
        const { data } = await axios.get(`/api/advices/${props.adviceId}/nextcloud/search`, {
            params: { q: searchQuery.value },
        });
        searchResults.value = data;
    } finally {
        searching.value = false;
    }
}

async function browse(path: string) {
    browsing.value = true;
    try {
        const { data } = await axios.get(`/api/advices/${props.adviceId}/nextcloud/browse`, {
            params: { path },
        });
        browseItems.value = data.items.filter((item: any) => !('size' in item));
        browsePath.value = data.path;
    } finally {
        browsing.value = false;
    }
}

function openDir(dir: App.Nextcloud.Data.NextcloudDir) {
    browseHistory.value.push(browsePath.value);
    browse(dir.path);
}

function browseUp() {
    const prev = browseHistory.value.pop();
    if (prev !== undefined) browse(prev);
}

async function linkFolder(dir: App.Nextcloud.Data.NextcloudDir) {
    linking.value = true;
    try {
        await axios.post(`/api/advices/${props.adviceId}/nextcloud/link`, {
            fileId: dir.fileId,
            path: dir.path,
        });
        open.value = false;
        router.reload({ only: ['advice'] });
    } finally {
        linking.value = false;
    }
}

// Trigger initial search on mount
search();
browse('/');
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Nextcloud-Ordner verknüpfen</DialogTitle>
            </DialogHeader>

            <Tabs default-value="search">
                <TabsList class="w-full">
                    <TabsTrigger value="search" class="flex-1">Suche</TabsTrigger>
                    <TabsTrigger value="browse" class="flex-1">Browser</TabsTrigger>
                </TabsList>

                <!-- Search Tab -->
                <TabsContent value="search" class="space-y-3 pt-2">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Suchbegriff…"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <Search class="absolute right-2.5 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <div v-if="searching" class="text-sm text-gray-500 py-4 text-center">Suche…</div>

                    <div v-else-if="searchResults.length === 0" class="text-sm text-gray-500 py-4 text-center">
                        Keine Treffer.
                    </div>

                    <ul v-else class="divide-y divide-gray-100 border rounded-md max-h-60 overflow-y-auto">
                        <li
                            v-for="dir in searchResults"
                            :key="dir.fileId"
                            class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer"
                            @click="linkFolder(dir)"
                        >
                            <Folder class="h-4 w-4 shrink-0 text-blue-400" />
                            <span class="flex-1 truncate">{{ dir.name }}</span>
                            <span class="text-xs text-gray-400 truncate max-w-32">{{ dir.path }}</span>
                        </li>
                    </ul>
                </TabsContent>

                <!-- Browse Tab -->
                <TabsContent value="browse" class="space-y-3 pt-2">
                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="browseHistory.length === 0"
                            @click="browseUp"
                        >
                            ← Zurück
                        </Button>
                        <span class="text-xs text-gray-500 truncate flex-1">{{ browsePath }}</span>
                    </div>

                    <div v-if="browsing" class="text-sm text-gray-500 py-4 text-center">Lädt…</div>

                    <div v-else-if="browseItems.length === 0" class="text-sm text-gray-500 py-4 text-center">
                        Keine Unterordner.
                    </div>

                    <ul v-else class="divide-y divide-gray-100 border rounded-md max-h-60 overflow-y-auto">
                        <li
                            v-for="dir in browseItems"
                            :key="dir.fileId"
                            class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"
                        >
                            <Folder class="h-4 w-4 shrink-0 text-blue-400" />
                            <span class="flex-1 truncate cursor-pointer" @click="openDir(dir)">{{ dir.name }}</span>
                            <Button variant="ghost" size="sm" :disabled="linking" @click="linkFolder(dir)">
                                Verknüpfen
                            </Button>
                            <ChevronRight
                                class="h-4 w-4 shrink-0 text-gray-400 cursor-pointer"
                                @click="openDir(dir)"
                            />
                        </li>
                    </ul>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>
