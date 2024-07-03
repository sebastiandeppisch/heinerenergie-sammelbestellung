<script setup lang="ts">

import { ref} from "vue";
import "leaflet/dist/leaflet.css";

import {
  LMap,
  LTileLayer,
  LMarker,
  LPopup,
  LIcon,
  LControl,
} from "@vue-leaflet/vue-leaflet";
import L from "leaflet";
import { latLng } from "leaflet";
import { store } from "../store";

interface Props {
  advisor: App.Models.User
}
const {advisor} = defineProps<Props>();
const zoom = ref(15);

</script>

<template>
  <div style="height: 300px; width: 100%" v-if="advisor.lat !== null && advisor.long">
    <LMap
      ref="map"
      :zoom="zoom"
      :center="latLng(advisor.lat, advisor.long)"
      :minZoom="3"
      :maxZoom="18"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />
      <LMarker
        :lat-lng="latLng(advisor.lat, advisor.long)"
      >
        <LIcon icon-url="/images/markers/he_yellow.svg" :icon-size="[50, 50]" />
      </LMarker>
    </LMap>
  </div>
  <div v-else>
    <i>Adresse kann nicht gefunden werden. Trage bitte eine gültige Adresse ein.</i>
  </div>
</template>
