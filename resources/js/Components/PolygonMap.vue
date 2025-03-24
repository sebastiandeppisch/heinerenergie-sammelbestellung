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


import L, { DrawEvents, LatLng, LatLngExpression, Point } from "leaflet";
import "leaflet-draw";

const CRS = L.CRS.Earth;

const props = withDefaults(defineProps<{
    logo: string | null,
    logoAspect: number | null
}>(), {
    logo: null,
    logoAspect: null
});

const logoSize = computed<Point>(() => {
  const height = 30;


  if(!props.logoAspect) {
    return new Point(height, height);
  }

  const width = height * props.logoAspect;
  return new Point(width, height);
});

type Coordinate = App.ValueObjects.Coordinate;

const polygonModel = defineModel<App.ValueObjects.Polygon>();

const mapRef = ref<typeof LMap | null>(null);

const centerOfPolygon = computed<Coordinate>(() => {

    const zeroCoordinate: Coordinate = {
        lat: 0,
        lng: 0
    };

    if(!polygonModel.value || polygonModel.value.coordinates.length === 0) {
        return zeroCoordinate;
    }
    return L.PolyUtil.polygonCenter(polygonModel.value?.coordinates, CRS);
});

async function onLeafletReady() {
    await nextTick();
    mapRef.value?.leafletObject.on(L.Draw.Event.CREATED, (e: DrawEvents.Created) => {
        const type = e.layerType;
        if (type === 'polygon') {
            const layer = e.layer as L.Polygon;
            const polygon = layer.getLatLngs();

            polygonModel.value = {
                coordinates: (polygon[0] as LatLng[]).map((item: LatLng) => {
                    if (typeof item === 'object') {
                        return {
                            lat: item.lat,
                            lng: item.lng
                        }
                    }
                    return {
                        lat: item[0],
                        lng: item[1]
                    };
                })
            };
        }else{
          console.log('other type');
          console.log(e.layerType);
          console.log(e.layer);
        }
    });
    centerAndZoomToContent();
}

function centerAndZoomToContent(){
  const polygon = polygonModel.value?.coordinates;

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

const coordinatedOfDarmstadtCityCenter = {
    lat: 49.8728,
    lng: 8.6512
}

const centerOfMap = CRS.project(coordinatedOfDarmstadtCityCenter);

</script>
<template>
    <LMap
      ref="mapRef"
      :zoom="15"
      :center="centerOfMap"
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
        <LIcon v-if="logo !== null" :icon-url="logo" :icon-size="logoSize" />
      </LMarker>

      <LPolygon v-for="(item, index) in [polygonModel]" :key="index" :lat-lngs="item.coordinates" v-if="polygonModel && polygonModel.coordinates.length > 0" />
    </LMap>
</template>
