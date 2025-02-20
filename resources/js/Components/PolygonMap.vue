<script setup lang="ts">
import { ref, nextTick, computed } from "vue";
import "leaflet-draw/dist/leaflet.draw.css";
import "leaflet-toolbar/dist/leaflet.toolbar.css";
import {
  LMap,
  LTileLayer,
  LFeatureGroup,
  LMarker,
  LPolygon,
  LIcon,
} from "@vue-leaflet/vue-leaflet";


import L, { DrawEvents, LatLng, LatLngExpression } from "leaflet";
import "leaflet-draw";

type Point = [number, number];
type Polygon = Point[];

const props = withDefaults(defineProps<{
  logo: string | null,
  logoAspect: number | null
}>(), {
  logo: null,
  logoAspect: null
});

const logoSize = computed(() => {
  const height = 30;


  if(!props.logoAspect) {
    return [height, height];
  }

  const width = height * props.logoAspect;
  return [width, height];
});

const polygonModel = defineModel<Polygon>();

const mapRef = ref<typeof LMap | null>(null);

const centerOfPolygon = computed<Point>(() => {

  if(!polygonModel.value) {
    return [0, 0];
  }
  const polygon = polygonModel.value;

  const center = polygon.reduce((acc: Point, curr: Point) => {
    return  [acc[0] + curr[0], acc[1] + curr[1]];
  }, [0, 0]);

  center[0] = center[0] / polygon.length;
  center[1] = center[1] / polygon.length;

  return center;
});

async function onLeafletReady() {
    await nextTick();
    mapRef.value?.leafletObject.on(L.Draw.Event.CREATED, (e: DrawEvents.Created) => {
        const type = e.layerType;
        if (type === 'polygon') {
            const layer = e.layer as L.Polygon;
            const polygon = layer.getLatLngs();

            polygonModel.value = (polygon[0] as LatLng[]).map((item: LatLng) => {
              if (typeof item === 'object') {
                return [item.lat, item.lng];
              }
              return item;
            });
        }else{
          console.log('other type');
          console.log(e.layerType);
          console.log(e.layer);
        }
    });
    centerAndZoomToContent();
}

function centerAndZoomToContent(){
  const polygon = polygonModel.value;

  if(!polygon || polygon.length === 0) {
    return;
  }

  const bounds = L.latLngBounds(polygon as LatLngExpression[]);
  mapRef.value?.leafletObject.fitBounds(bounds);
}

async function onFeatureGroupReady() {
    await nextTick();
    const drawControl = new L.Control.Draw({
        position: 'topright',
        draw: {
            polygon: {
                allowIntersection: false, // Restricts shapes to simple polygons
                drawError: {
                    color: '#e1e100', // Color the shape will turn when intersects
                    message: 'Die Linien dürfen sich nicht schneiden' // Message that will show when intersect
                },
                shapeOptions: {
                    color: '#97009c'
                }
            },
            // disable toolbar item by setting it to false
            polyline: false,
            circle: false,
            rectangle: false,
            marker: false,
            circlemarker: false,
        }
    });
    mapRef.value?.leafletObject.addControl(drawControl);
}

const coordinatedOfDarmstadtCityCenter = [49.8728, 8.6512];

</script>
<template>
    <LMap
      ref="mapRef"
      :zoom="15"
      :center="coordinatedOfDarmstadtCityCenter"
      use-global-leaflet
      :options="{attributionControl: false}"
      @ready="onLeafletReady"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />
      <LFeatureGroup @ready="onFeatureGroupReady" />
     <LMarker :lat-lng="centerOfPolygon">
        <LIcon :icon-url="logo" :icon-size="logoSize" />
      </LMarker>
    
      <LPolygon v-for="(item, index) in [polygonModel]" :key="index" :lat-lngs="item" v-if="polygonModel && polygonModel.length > 0" />
    </LMap>
</template> 