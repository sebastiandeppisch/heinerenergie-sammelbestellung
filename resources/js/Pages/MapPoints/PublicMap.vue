<script setup lang="ts">
import { reactive, watch } from "vue";
import "leaflet/dist/leaflet.css";
import {
  LMap,
  LTileLayer,
  LMarker,
  LPopup,
  LLayerGroup,
  LControlLayers,
} from "@vue-leaflet/vue-leaflet";
import { latLng } from "leaflet";
import PublicLayout from '@/layouts/PublicLayout.vue';
import NoLayout from '@/layouts/NoLayout.vue';
import { isIframe } from '@/helpers';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout
});

const props = defineProps<{
  pointsByType: Record<string, Array<App.Data.MapPointData>>;
}>();

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


// Update URL hash when map changes
watch(map, () => {
  window.location.hash = '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng;
});
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
        <LControlLayers
          :collapsed="false"
          :hide-single-base="true"
        />
        <LTileLayer
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          layer-type="base"
          name="OpenStreetMap"
        />

        <LLayerGroup
          v-for="(points, type) in pointsByType"
          :key="type"
          :name="type"
          layer-type="overlay"
        >
          <LMarker
            v-for="point in points"
            :key="point.id"
            :lat-lng="latLng(point.coordinate.lat, point.coordinate.lng)"
          >
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
  </div>
</template>

<style scoped>
</style>
