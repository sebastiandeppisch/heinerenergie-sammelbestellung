<script setup lang="ts">
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import { Button } from '@/shadcn/components/ui/button';
import { categoryMarkerIcon } from '@/utils/categoryMarkerIcon';
import { Link } from '@inertiajs/vue3';
import { Mail, Minus, Plus } from '@lucide/vue';
import { LControl, LIcon, LLayerGroup, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { latLng } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ref } from 'vue';
import { route } from 'ziggy-js';

type MapPoint = Pick<App.Data.PublicMapPointData, 'id' | 'coordinate' | 'title' | 'description' | 'fields' | 'characteristics'> & {
    has_contact?: boolean;
};

const props = withDefaults(
    defineProps<{
        pointsByCategory: Record<string, Array<MapPoint>>;
        categories: Array<App.Data.MapPointCategoryData>;
        center: { lat: number; lng: number };
        zoom: number;
        minZoom?: number;
        maxZoom?: number;
        contactUrl?: (pointId: string) => string;
        /** On the public map, the person behind a point can request a link to manage it. */
        showManageLink?: boolean;
    }>(),
    {
        minZoom: 3,
        maxZoom: 18,
        showManageLink: false,
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
                    <LIcon v-bind="categoryMarkerIcon(category.marker_image_path)" />
                    <LPopup>
                        <div class="p-2">
                            <h3 class="text-lg font-bold">{{ point.title }}</h3>
                            <p class="text-sm">{{ point.description }}</p>
                            <!-- The public map only receives public fields, so internal ones are marked for admins only. -->
                            <MapPointFieldList
                                v-if="point.fields.length > 0 || point.characteristics.length > 0"
                                :fields="point.fields"
                                :characteristics="point.characteristics"
                                mark-internal
                                class="mt-2"
                            />
                            <Button v-if="point.has_contact && contactUrl" as-child size="sm" class="mt-2">
                                <Link :href="contactUrl(point.id)" class="!text-primary-foreground">
                                    <Mail class="h-4 w-4" />
                                    Kontakt aufnehmen
                                </Link>
                            </Button>
                            <a
                                v-if="showManageLink"
                                :href="route('map-point.manage.request-for-point', point.id)"
                                target="_blank"
                                rel="noopener"
                                class="mt-2 block text-xs text-gray-500 underline"
                            >
                                Eintrag verwalten
                            </a>
                        </div>
                    </LPopup>
                </LMarker>
            </LLayerGroup>
        </LMap>
    </div>
</template>
