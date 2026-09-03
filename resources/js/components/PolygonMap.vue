<script setup lang="ts">
import { LFeatureGroup, LIcon, LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import 'leaflet-draw/dist/leaflet.draw.css';
import 'leaflet-toolbar/dist/leaflet.toolbar.css';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, watch } from 'vue';

import L, { DrawEvents, LatLng, LatLngExpression, Point, PointExpression } from 'leaflet';
import 'leaflet-draw';

// leaflet-draw 0.4 tells a flat ring from a list of rings with L.Polyline._flat,
// which leaflet only keeps as a deprecated alias that logs a warning on every
// call while editing. Point it at its replacement to keep the console clean.
const polylineWithLegacyHelper = L.Polyline as unknown as { _flat: typeof L.LineUtil.isFlat };
polylineWithLegacyHelper._flat = L.LineUtil.isFlat;

L.drawLocal.draw.toolbar.actions.title = 'Zeichnen abbrechen';
L.drawLocal.draw.toolbar.actions.text = 'Abbrechen';
L.drawLocal.draw.toolbar.finish.title = 'Zeichnen beenden';
L.drawLocal.draw.toolbar.finish.text = 'Fertig';
L.drawLocal.draw.toolbar.undo.title = 'Zuletzt gesetzten Punkt entfernen';
L.drawLocal.draw.toolbar.undo.text = 'Letzten Punkt entfernen';
L.drawLocal.draw.toolbar.buttons.polygon = 'Gebiet zeichnen';
L.drawLocal.draw.handlers.polygon.tooltip.start = 'Klicke, um das Gebiet zu zeichnen.';
L.drawLocal.draw.handlers.polygon.tooltip.cont = 'Klicke, um weiterzuzeichnen.';
L.drawLocal.draw.handlers.polygon.tooltip.end = 'Klicke auf den ersten Punkt, um das Gebiet zu schließen.';
L.drawLocal.draw.handlers.polyline.error = '<strong>Fehler:</strong> Die Linien dürfen sich nicht schneiden!';
L.drawLocal.edit.toolbar.actions.save.title = 'Änderungen übernehmen';
L.drawLocal.edit.toolbar.actions.save.text = 'Übernehmen';
L.drawLocal.edit.toolbar.actions.cancel.title = 'Bearbeiten abbrechen und Änderungen verwerfen';
L.drawLocal.edit.toolbar.actions.cancel.text = 'Abbrechen';
L.drawLocal.edit.toolbar.buttons.edit = 'Gebiet bearbeiten';
L.drawLocal.edit.toolbar.buttons.editDisabled = 'Es gibt noch kein Gebiet zum Bearbeiten';
L.drawLocal.edit.handlers.edit.tooltip.text =
    'Ziehe die Punkte, um das Gebiet anzupassen. Ein Klick auf einen Punkt löscht ihn, ein Klick auf einen halbtransparenten Punkt dazwischen fügt einen neuen hinzu.';
L.drawLocal.edit.handlers.edit.tooltip.subtext = 'Klicke auf Abbrechen, um die Änderungen zu verwerfen.';

const props = withDefaults(
    defineProps<{
        logo: string | null;
        logoAspect: number | null;
    }>(),
    {
        logo: null,
        logoAspect: null,
    },
);

const logoSize = computed<Point>(() => {
    const height = 30;

    if (!props.logoAspect) {
        return new Point(height, height);
    }

    const width = height * props.logoAspect;
    return new Point(width, height);
});

type Coordinate = App.ValueObjects.Coordinate;

const polygonModel = defineModel<App.ValueObjects.Polygon>();

const emit = defineEmits<{
    // A brand new area was drawn, as opposed to an existing one being reshaped.
    drawn: [];
}>();

const mapRef = ref<typeof LMap | null>(null);
const featureGroupRef = ref<typeof LFeatureGroup | null>(null);

// The polygon is kept inside the feature group instead of being rendered as its
// own component, because leaflet-draw can only edit layers of a feature group.
let polygonLayer: L.Polygon | null = null;
let drawHandler: L.Draw.Polygon | null = null;
let drawingRequested = false;

// The saved area takes its color from the class below. The shape being drawn
// cannot, because leaflet-draw recolors it through setStyle() while the lines
// intersect, and a stylesheet would win over that.
const polygonClass = 'consulting-area-polygon';

function primaryColor(): string {
    return getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
}

function polygonDrawOptions() {
    return {
        allowIntersection: false, // Restricts shapes to simple polygons
        drawError: {
            color: '#e1e100', // Color the shape will turn when intersects
            message: 'Die Linien dürfen sich nicht schneiden', // Message that will show when intersect
        },
        shapeOptions: {
            color: primaryColor(),
        },
    };
}

const centerOfPolygon = computed<Coordinate>(() => {
    const zeroCoordinate: Coordinate = {
        lat: 0,
        lng: 0,
    };

    if (!polygonModel.value || polygonModel.value.coordinates.length === 0) {
        return zeroCoordinate;
    }

    const coordinates = polygonModel.value.coordinates;

    const sum = coordinates.reduce((acc: Coordinate, curr: Coordinate) => {
        return {
            lat: acc.lat + curr.lat,
            lng: acc.lng + curr.lng,
        };
    }, zeroCoordinate);

    return {
        lat: sum.lat / coordinates.length,
        lng: sum.lng / coordinates.length,
    };
});

function toCoordinates(latLngs: LatLng[]): Coordinate[] {
    return latLngs.map((latLng: LatLng) => ({
        lat: latLng.lat,
        lng: latLng.lng,
    }));
}

function ringOf(layer: L.Polygon): Coordinate[] {
    return toCoordinates(layer.getLatLngs()[0] as LatLng[]);
}

/**
 * Tells whether the layer on the map already shows what the model holds. Used to
 * leave the layer untouched while it is being drawn or edited.
 */
function layerMatchesModel(): boolean {
    const coordinates = polygonModel.value?.coordinates ?? [];
    const ring = polygonLayer ? ringOf(polygonLayer) : [];

    return (
        coordinates.length === ring.length &&
        coordinates.every((coordinate, index) => coordinate.lat === ring[index].lat && coordinate.lng === ring[index].lng)
    );
}

function renderPolygon() {
    const featureGroup = featureGroupRef.value?.leafletObject as L.FeatureGroup | undefined;

    if (!featureGroup) {
        return;
    }

    if (polygonLayer) {
        featureGroup.removeLayer(polygonLayer);
        polygonLayer = null;
    }

    const coordinates = polygonModel.value?.coordinates;

    if (!coordinates || coordinates.length === 0) {
        return;
    }

    polygonLayer = L.polygon(coordinates as LatLngExpression[], { className: polygonClass });
    featureGroup.addLayer(polygonLayer);
}

watch(
    () => polygonModel.value,
    () => {
        if (!layerMatchesModel()) {
            renderPolygon();
        }
    },
    { deep: true },
);

async function onLeafletReady() {
    await nextTick();

    const map = mapRef.value?.leafletObject;

    map?.on(L.Draw.Event.CREATED, (e: DrawEvents.Created) => {
        if (e.layerType !== 'polygon') {
            return;
        }

        const layer = e.layer as L.Polygon;

        polygonModel.value = { coordinates: ringOf(layer) };
        emit('drawn');
    });

    map?.on(L.Draw.Event.EDITED, (e: DrawEvents.Edited) => {
        e.layers.eachLayer((layer) => {
            polygonModel.value = { coordinates: ringOf(layer as L.Polygon) };
        });
    });

    centerAndZoomToContent();
}

function centerAndZoomToContent() {
    const polygon = polygonModel.value?.coordinates;

    if (!polygon || polygon.length === 0) {
        return;
    }

    const bounds = L.latLngBounds(polygon as LatLngExpression[]);
    mapRef.value?.leafletObject.fitBounds(bounds);
}

async function onFeatureGroupReady() {
    await nextTick();

    const featureGroup = featureGroupRef.value?.leafletObject as L.FeatureGroup | undefined;

    if (!featureGroup) {
        return;
    }

    renderPolygon();

    const drawControl = new L.Control.Draw({
        position: 'topright',
        draw: {
            polygon: polygonDrawOptions(),
            // disable toolbar item by setting it to false
            polyline: false,
            circle: false,
            rectangle: false,
            marker: false,
            circlemarker: false,
        },
        edit: {
            featureGroup,
            // The area is removed with the button below the map instead.
            remove: false,
        },
    });

    const map = mapRef.value?.leafletObject;
    map?.addControl(drawControl);

    // Kept around so that drawing can also be started from outside the map.
    drawHandler = new L.Draw.Polygon(map, polygonDrawOptions());

    if (drawingRequested) {
        drawingRequested = false;
        drawHandler.enable();
    }
}

/**
 * Starts drawing a new area, just like the polygon button on the map does.
 *
 * The map needs a moment to set itself up, so a request that arrives before
 * that is remembered instead of being dropped.
 */
function startDrawing() {
    if (!drawHandler) {
        drawingRequested = true;

        return;
    }

    drawHandler.enable();
}

defineExpose({ centerAndZoomToContent, startDrawing });

const coordinatedOfDarmstadtCityCenter: PointExpression = [49.8728, 8.6512];
</script>
<template>
    <div class="isolate h-full w-full">
        <LMap
            ref="mapRef"
            :zoom="15"
            :center="coordinatedOfDarmstadtCityCenter"
            use-global-leaflet
            :options="{ attributionControl: false }"
            @ready="onLeafletReady"
        >
            <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />
            <LFeatureGroup ref="featureGroupRef" @ready="onFeatureGroupReady" />
            <LMarker :lat-lng="centerOfPolygon">
                <LIcon v-if="logo !== null" :icon-url="logo" :icon-size="logoSize" />
            </LMarker>
        </LMap>
    </div>
</template>

<style scoped>
/* Leaflet draws the polygon itself, so the app color reaches it through the
   class name given to the layer. Going through the stylesheet keeps it in step
   with theme changes and with the color picker in the group settings. */
:deep(.consulting-area-polygon) {
    stroke: var(--primary);
    fill: var(--primary);
}
</style>
