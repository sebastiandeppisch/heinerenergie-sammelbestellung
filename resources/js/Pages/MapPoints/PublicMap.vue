<script setup lang="ts">
import CategorizedPointsMap from '@/components/CategorizedPointsMap.vue';
import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import MapPointsTable from '@/components/MapPointsTable.vue';
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { Map as MapIcon, Table as TableIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
    categories: Array<App.Data.MapPointCategoryData>;
    center: App.ValueObjects.Coordinate;
    zoom: number;
    showTable: boolean;
}>();

useAutoResizeIframeIfIsIframe();

const activeTab = ref<'map' | 'table'>('map');

const map = reactive({
    center: { lat: props.center.lat, lng: props.center.lng },
    zoom: props.zoom,
});

const categoryVisibility = reactive<Record<string, boolean>>(Object.fromEntries(props.categories.map((category) => [category.id, true])));

const mapHeightClass = computed(() => {
    if (isIframe) {
        return 'h-[520px]';
    }
    return props.showTable ? 'h-[calc(100vh-250px)]' : 'h-[calc(100vh-220px)]';
});

const visibleCategories = computed(() => props.categories.filter((category) => categoryVisibility[category.id]));

// Check if hash exists in URL for map position
const hash = window.location.hash;
if (hash !== '') {
    const parts = hash.replace('#', '').split('/');
    map.zoom = parseInt(parts[0]);
    map.center.lat = parseFloat(parts[1]);
    map.center.lng = parseFloat(parts[2]);
}

// Update URL hash when map changes
watch(map, () => {
    window.location.hash = '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng;
});
</script>

<template>
    <div>
        <Tabs v-model="activeTab">
            <TabsList v-if="showTable">
                <TabsTrigger value="map" class="flex items-center gap-2">
                    <MapIcon class="h-4 w-4" />
                    Karte
                </TabsTrigger>
                <TabsTrigger value="table" class="flex items-center gap-2">
                    <TableIcon class="h-4 w-4" />
                    Tabelle
                </TabsTrigger>
            </TabsList>

            <TabsContent value="map">
                <div class="relative w-full overflow-hidden rounded-xl border shadow-sm" :class="mapHeightClass">
                    <div v-if="categories.length > 1" class="absolute top-2 right-2 z-[1000] rounded-lg bg-white/90 p-3 shadow">
                        <CategoryVisibilityFilter v-model:visibility="categoryVisibility" :categories="categories" id-prefix="map-category-" />
                    </div>

                    <CategorizedPointsMap
                        v-model:center="map.center"
                        v-model:zoom="map.zoom"
                        :points-by-category="pointsByCategory"
                        :categories="visibleCategories"
                    />
                </div>
            </TabsContent>

            <TabsContent v-if="showTable" value="table">
                <MapPointsTable :points-by-category="pointsByCategory" :categories="categories" />
            </TabsContent>
        </Tabs>
    </div>
</template>

<style scoped></style>
