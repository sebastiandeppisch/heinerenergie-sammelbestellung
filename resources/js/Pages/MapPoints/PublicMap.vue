<script setup lang="ts">
import CategorizedPointsMap from '@/components/CategorizedPointsMap.vue';
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';
import { computed, reactive, watch } from 'vue';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
    categories: Array<App.Data.MapPointCategoryData>;
    center: App.ValueObjects.Coordinate;
    zoom: number;
}>();

useAutoResizeIframeIfIsIframe();

const map = reactive({
    center: { lat: props.center.lat, lng: props.center.lng },
    zoom: props.zoom,
});

const categoryVisibility = reactive<Record<string, boolean>>(Object.fromEntries(props.categories.map((category) => [category.id, true])));

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
    <div class="relative w-full" :class="isIframe ? 'h-[520px]' : 'h-[calc(100vh-220px)]'">
        <div v-if="categories.length > 1" class="absolute top-2 right-2 z-[1000] space-y-2 rounded-lg bg-white/90 p-3 shadow">
            <div v-for="category in categories" :key="category.id" class="flex items-center gap-2">
                <Checkbox :id="'category-' + category.id" v-model="categoryVisibility[category.id]" />
                <Label :for="'category-' + category.id" class="flex items-center gap-2 text-sm font-normal">
                    <div v-if="category.image_path" class="h-5 w-5 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                        <img :src="category.image_path" :alt="category.name" class="h-full w-full object-cover" />
                    </div>
                    {{ category.name }}
                </Label>
            </div>
        </div>

        <CategorizedPointsMap
            v-model:center="map.center"
            v-model:zoom="map.zoom"
            :points-by-category="pointsByCategory"
            :categories="visibleCategories"
        />
    </div>
</template>

<style scoped></style>
