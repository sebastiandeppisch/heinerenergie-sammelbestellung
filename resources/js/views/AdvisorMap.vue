<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import { ref } from 'vue';

import { LCircle, LIcon, LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { latLng } from 'leaflet';

const props = defineProps<{
    advisor: App.Data.UserData;
    advisorMarker: string;
}>();
const zoom = ref(15);
</script>

<template>
    <div style="height: 300px; width: 100%" class="isolate" v-if="props.advisor.lat !== null && props.advisor.long">
        <LMap ref="map" :zoom="zoom" :center="[props.advisor.lat, props.advisor.long]" :minZoom="3" :maxZoom="18">
            <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />
            <LMarker :lat-lng="latLng(props.advisor.lat, props.advisor.long)">
                <LIcon :icon-url="props.advisorMarker" :icon-size="[50, 50]" />
            </LMarker>
            <LCircle
                v-if="props.advisor.advice_radius"
                :lat-lng="latLng(props.advisor.lat, props.advisor.long)"
                :radius="props.advisor.advice_radius"
            />
        </LMap>
    </div>
    <div v-else>
        <i>Adresse kann nicht gefunden werden. Trage bitte eine gültige Adresse ein.</i>
    </div>
</template>
