<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { LCircle, LIcon, LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { LatLngBounds, latLng } from 'leaflet';

const props = defineProps<{
    advisor: App.Data.UserData;
    advisorMarker: string;
}>();
const zoom = ref(15);

const mapRef = useTemplateRef<typeof LMap>('mapRef');

const mapKey = computed<string>(() => {
    if (props.advisor.lat === null || props.advisor.long === null || props.advisor.lat === undefined || props.advisor.long === undefined) {
        return 'null';
    }

    return props.advisor.lat.toString() + props.advisor.long.toString();
});

const center = computed(() => {
    if (props.advisor.lat === null || props.advisor.long === null || props.advisor.lat === undefined || props.advisor.long === undefined) {
        return null;
    }

    return latLng(props.advisor.lat, props.advisor.long);
});

/**
 * Bounding box of the advice radius circle, used to zoom the map so the whole circle fits.
 */
const circleBounds = computed<LatLngBounds | null>(() => {
    if (props.advisor.lat === null || props.advisor.long === null || props.advisor.lat === undefined || props.advisor.long === undefined) {
        return null;
    }

    if (!props.advisor.advice_radius) {
        return null;
    }

    return latLng(props.advisor.lat, props.advisor.long).toBounds(props.advisor.advice_radius * 2);
});

function fitToCircle(): void {
    if (circleBounds.value === null) {
        return;
    }

    mapRef.value?.leafletObject?.fitBounds(circleBounds.value, { padding: [10, 10] });
}

async function onLeafletReady(): Promise<void> {
    await nextTick();
    fitToCircle();
}

watch(circleBounds, fitToCircle);
</script>

<template>
    <div style="height: 300px; width: 100%" class="isolate" v-if="center">
        <LMap :key="mapKey" ref="mapRef" :zoom="zoom" :center="[center.lat, center.lng]" :minZoom="3" :maxZoom="18" @ready="onLeafletReady">
            <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />
            <LMarker :lat-lng="center!">
                <LIcon :icon-url="props.advisorMarker" :icon-size="[50, 50]" />
            </LMarker>
            <LCircle v-if="props.advisor.advice_radius" :lat-lng="center!" :radius="props.advisor.advice_radius" />
        </LMap>
    </div>
    <div v-else>
        <i>Adresse kann nicht gefunden werden. Trage bitte eine gültige Adresse ein.</i>
    </div>
</template>
