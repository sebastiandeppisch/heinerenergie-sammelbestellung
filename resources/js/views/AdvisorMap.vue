<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import { computed, ref } from 'vue';

import { LCircle, LIcon, LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { latLng } from 'leaflet';

const { advisor, advisorMarker } = defineProps<{
    advisor: App.Data.UserData;
    advisorMarker?: string;
}>();
const zoom = ref(15);


function getAdvisorMarker(): string {
    return advisorMarker ?? '/images/markers/he_yellow.svg';
}

const mapKey = computed<string>(()=>{
    if(advisor.lat === null || advisor.long === null || advisor.lat === undefined || advisor.long === undefined){
        return 'null';
    }

    return advisor.lat.toString() + advisor.long.toString();
});

</script>

<template>
    <div style="height: 300px; width: 100%" class="isolate" v-if="advisor.lat !== null && advisor.long !== null">
        <LMap :key="mapKey" ref="map" :zoom="zoom" :center="[advisor.lat, advisor.long]" :minZoom="3" :maxZoom="18">
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
