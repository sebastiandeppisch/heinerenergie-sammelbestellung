<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { LCircle, LIcon, LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { LatLngBounds, latLng } from 'leaflet';

const { advisor, advisorMarker } = defineProps<{
    advisor: App.Data.UserData;
    advisorMarker?: string;
}>();
const zoom = ref(15);

const mapRef = useTemplateRef<typeof LMap>('mapRef');

function getAdvisorMarker(): string {
    return advisorMarker ?? '/images/markers/he_yellow.svg';
}

const mapKey = computed<string>(() => {
    if (advisor.lat === null || advisor.long === null || advisor.lat === undefined || advisor.long === undefined) {
        return 'null';
    }

    return advisor.lat.toString() + advisor.long.toString();
});

/**
 * Bounding box of the advice radius circle, used to zoom the map so the whole circle fits.
 */
const circleBounds = computed<LatLngBounds | null>(() => {
    if (advisor.lat === null || advisor.long === null || advisor.lat === undefined || advisor.long === undefined) {
        return null;
    }

    if (!advisor.advice_radius) {
        return null;
    }

    return latLng(advisor.lat, advisor.long).toBounds(advisor.advice_radius * 2);
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
    <div style="height: 300px; width: 100%" class="isolate" v-if="advisor.lat !== null && advisor.long !== null">
        <LMap :key="mapKey" ref="mapRef" :zoom="zoom" :center="[advisor.lat, advisor.long]" :minZoom="3" :maxZoom="18" @ready="onLeafletReady">
            <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />
            <LMarker :lat-lng="latLng(advisor.lat, advisor.long)">
                <LIcon :icon-url="getAdvisorMarker()" :icon-size="[50, 50]" />
            </LMarker>
            <LCircle v-if="advisor.advice_radius" :lat-lng="latLng(advisor.lat, advisor.long)" :radius="advisor.advice_radius" />
        </LMap>
    </div>
    <div v-else>
        <i>Adresse kann nicht gefunden werden. Trage bitte eine gültige Adresse ein.</i>
    </div>
</template>
