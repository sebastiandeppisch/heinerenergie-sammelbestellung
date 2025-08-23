<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { router } from '@inertiajs/vue3';
import { LControlLayers, LIcon, LLayerGroup, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { latLng } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Link } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import { route } from 'ziggy-js';
// Define props
const props = defineProps<{
    pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
    categories: Array<App.Data.MapPointCategoryData>;
}>();

// Map configuration
const map = reactive({
    center: latLng(49.8728, 8.6512) as { lat: number; lng: number },
    zoom: 15,
});

// Check if hash exists in URL for map position
const hash = window.location.hash;
if (hash !== '') {
    const parts = hash.replace('#', '').split('/');
    map.zoom = parseInt(parts[0]);
    map.center.lat = parseFloat(parts[1]);
    map.center.lng = parseFloat(parts[2]);
}

function zoomChanged(e: number) {
    map.zoom = e;
}

function centerChanged(e: { lat?: number; lng?: number }) {
    if ('lat' in e === false || 'lng' in e === false) {
        return;
    }

    if (e.lat === undefined || e.lng === undefined) {
        return;
    }

    map.center.lat = e.lat;
    map.center.lng = e.lng;
}

// Format pointable type name for display
function formatPointableType(type: string): string {
    // Extract the class name from the full namespace
    const className = type.split('\\').pop() || type;

    // Convert camelCase to spaces (e.g., "SomeClass" to "Some Class")
    return className.replace(/([A-Z])/g, ' $1').trim();
}

// Update URL hash when map changes
watch(map, () => {
    window.location.hash = '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng;
});

function categoryIdToName(category_id: string): string {
    const category = props.categories.find((cat) => cat.id === category_id);
    return category ? category.name : 'Unbekannte Kategorie';
}

function categoryIdToImagePath(category_id: string): string | undefined {
    const category = props.categories.find((cat) => cat.id === category_id);
    return category && category.image_path ? category.image_path : undefined;
}
</script>

<template>
    <div class="h-screen w-full">
        <div class="h-full w-full">
            <LMap
                ref="map"
                :zoom="map.zoom"
                @update:zoom="zoomChanged"
                :center="[map.center.lat, map.center.lng]"
                @update:center="centerChanged"
                :minZoom="3"
                :maxZoom="18"
            >
                <LControlLayers :collapsed="false" :hide-single-base="true" />
                <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />

                <LLayerGroup
                    v-for="(points, category_id) in pointsByCategory"
                    :key="category_id"
                    :name="categoryIdToName(category_id)"
                    layer-type="overlay"
                >
                    <LMarker v-for="point in points" :key="point.id" :lat-lng="latLng(point.coordinate.lat, point.coordinate.lng)">
                        <LIcon
                            v-if="categoryIdToImagePath(category_id) !== null"
                            :icon-url="categoryIdToImagePath(category_id)"
                            :icon-size="[50, 50]"
                        />
                        <LPopup>
                            <div class="p-2">
                                <h3 class="text-lg font-bold">{{ point.title }}</h3>
                                <p class="text-sm">{{ point.description }}</p>
                                <Button variant="outline" @click="router.visit(route('mappoints.edit', point.id))"> <Link /> Punkt öffnen </Button>
                            </div>
                        </LPopup>
                    </LMarker>
                </LLayerGroup>
            </LMap>
        </div>
    </div>
</template>

<style scoped></style>
