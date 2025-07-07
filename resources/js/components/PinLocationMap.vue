<script setup lang="ts">
import { ref, nextTick, onMounted, computed } from "vue";
import {
  LMap,
  LTileLayer,
  LMarker,
  LControl,
} from "@vue-leaflet/vue-leaflet";

import L, { LatLngExpression, PointExpression } from "leaflet";
import { Loader2, Locate } from "lucide-vue-next";
import Button from "@/shadcn/components/ui/button/Button.vue";
import { useOnResize } from "@/helpers";

type Coordinate = App.ValueObjects.Coordinate;

const locationModel = defineModel<Coordinate | null>({
  default: null
});

const props = withDefaults(defineProps<{
    readonly?: boolean;
}>(), {
    readonly: false
});


const mapRef = ref<typeof LMap | null>(null);
const mapContainerRef = ref<HTMLDivElement | null>(null);
const isLocating = ref(false);
const locationError = ref<string | null>(null);

const updateMapHeight = () => {
  if (mapContainerRef.value) {
    const width = mapContainerRef.value.offsetWidth;
    const height = width * (2/3);
    mapContainerRef.value.style.height = `${height}px`;

    if (mapRef.value?.leafletObject) {
      mapRef.value.leafletObject.invalidateSize();
    }
  }
};

useOnResize(updateMapHeight);

async function onLeafletReady() {
  await nextTick();

  if(! props.readonly) {
    mapRef.value?.leafletObject.on('click', (event: L.LeafletMouseEvent) => {
        locationModel.value = {
            lat: event.latlng.lat,
            lng: event.latlng.lng
        };
    });

  }


  updateMapHeight();
}

function locateUser() {
  isLocating.value = true;
  locationError.value = null;

  if (!navigator.geolocation) {
    locationError.value = "Geolokalisierung wird von Deinem Browser nicht unterstützt.";
    isLocating.value = false;
    return;
  }

  navigator.geolocation.getCurrentPosition(
    // Erfolg
    (position) => {
      locationModel.value = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };

      // Karte auf die Position zentrieren und zoomen
      mapRef.value?.leafletObject.setView(
        [position.coords.latitude, position.coords.longitude],
        16
      );

      isLocating.value = false;
    },
    // Fehler
    (error) => {
      switch(error.code) {
        case error.PERMISSION_DENIED:
          locationError.value = "Zugriff auf Standort wurde verweigert.";
          break;
        case error.POSITION_UNAVAILABLE:
          locationError.value = "Standortinformationen sind nicht verfügbar.";
          break;
        case error.TIMEOUT:
          locationError.value = "Zeitüberschreitung bei der Standortabfrage.";
          break;
        default:
          locationError.value = "Ein unbekannter Fehler ist aufgetreten.";
          break;
      }
      isLocating.value = false;
    },
    // Optionen
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
}

const coordinatesOfDarmstadtCityCenter: PointExpression = [49.8728, 8.6512];

onMounted(() => {
    if (locationModel.value as any === '') {
        locationModel.value = null;
    }
    updateMapHeight();
});

const centerOfMap = computed<PointExpression>(() => {
  if (locationModel.value) {
    return [locationModel.value.lat, locationModel.value.lng];
  }
  return coordinatesOfDarmstadtCityCenter;
});


</script>

<template>
  <div class="pin-location-map-container">
    <div ref="mapContainerRef" class="map-container">
      <LMap
        ref="mapRef"
        :zoom="15"
        :center="centerOfMap"
        use-global-leaflet
        :options="{attributionControl: false}"
        @ready="onLeafletReady"
        style="width: 100%; height: 100%;"
      >
        <LTileLayer
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          layer-type="base"
          name="OpenStreetMap"
        />

        <!-- Standard Leaflet Marker ohne benutzerdefiniertes Icon -->
        <LMarker :lat-lng="locationModel" v-if="locationModel !==null && locationModel.lat !== undefined && locationModel.lng !== undefined" />

        <!-- Benutzerdefiniertes Kontrollelement für GPS-Lokalisierung -->
        <LControl position="topright" v-if="!props.readonly">
          <div>
            <Button
                @click.prevent="locateUser"
                :variant="isLocating? 'outline' : 'default'"
                :disabled="isLocating"
                class="w-60"
            >
                <div v-if="isLocating" class="flex items-center">
                    <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                    Standort wird geladen...
                </div>
                <div v-else class="flex items-center">
                    <Locate class="mr-1"/>
                    Aktuellen Standort verwenden
                </div>

            </Button>
          </div>
        </LControl>
      </LMap>
    </div>

    <div v-if="locationError" class="location-error">
      {{ locationError }}
    </div>

  </div>
</template>

<style scoped>
.pin-location-map-container {
    width: 100%;
}

.map-container {
    width: 100%;
    min-height: 200px; /* Fallback Mindesthöhe */
    background-color: #f0f0f0;
    position: relative;
    overflow: hidden;
    border-radius: 4px;
}

.location-error {
    color: #e53e3e;
    margin-top: 8px;
    padding: 8px;
    background-color: #fff5f5;
    border-radius: 4px;
    border: 1px solid #fc8181;
}
</style>
