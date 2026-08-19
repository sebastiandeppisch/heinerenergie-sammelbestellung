<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { LControl, LIcon, LLayerGroup, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { latLng } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Minus, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
        categories: Array<App.Data.MapPointCategoryData>;
        center: { lat: number; lng: number };
        zoom: number;
        minZoom?: number;
        maxZoom?: number;
    }>(),
    {
        minZoom: 3,
        maxZoom: 18,
    },
);

const emit = defineEmits<{
    'update:center': [{ lat: number; lng: number }];
    'update:zoom': [number];
}>();

function zoomChanged(zoom: number) {
    emit('update:zoom', zoom);
}

function centerChanged(center: { lat?: number; lng?: number }) {
    if (center.lat === undefined || center.lng === undefined) {
        return;
    }

    emit('update:center', { lat: center.lat, lng: center.lng });
}

const mapRef = ref<typeof LMap | null>(null);

function zoomIn() {
    mapRef.value?.leafletObject?.zoomIn();
}

function zoomOut() {
    mapRef.value?.leafletObject?.zoomOut();
}
</script>

<template>
    <div class="isolate h-full w-full">
        <LMap
            ref="mapRef"
            :zoom="props.zoom"
            @update:zoom="zoomChanged"
            :center="[props.center.lat, props.center.lng]"
            @update:center="centerChanged"
            :minZoom="props.minZoom"
            :maxZoom="props.maxZoom"
            :options="{ zoomControl: false }"
        >
            <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />

            <LControl position="topleft">
                <div class="flex flex-col overflow-hidden rounded-md border bg-background shadow-sm">
                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded-none" @click="zoomIn">
                        <Plus class="h-4 w-4" />
                    </Button>
                    <div class="h-px bg-border" />
                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded-none" @click="zoomOut">
                        <Minus class="h-4 w-4" />
                    </Button>
                </div>
            </LControl>

            <LLayerGroup v-for="category in categories" :key="category.id" :name="category.name" layer-type="overlay">
                <LMarker
                    v-for="point in pointsByCategory[category.id] ?? []"
                    :key="point.id"
                    :lat-lng="latLng(point.coordinate.lat, point.coordinate.lng)"
                >
                    <LIcon v-if="category.image_path" :icon-url="category.image_path" :icon-size="[50, 50]" />
                    <LPopup>
                        <div class="p-2">
                            <h3 class="text-lg font-bold">{{ point.title }}</h3>
                            <p class="text-sm">{{ point.description }}</p>
                        </div>
                    </LPopup>
                </LMarker>
            </LLayerGroup>
        </LMap>
    </div>
</template>
