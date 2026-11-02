<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { X } from '@lucide/vue';
import './hexnet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import {
    BASEMAPS,
    type Basemap,
    cellCenter,
    type HexCells,
    LEVELS,
    levelFor,
    MEASURE_OPACITY,
    type MarkerStyle,
    type Thresholds,
    ZOOM_LIGHT_CELLS,
} from './hexnet';
import { markerFor } from './markers';

export type CellSelection = { id: string; count: number; level: number; center: { lat: number; lng: number } };

const props = withDefaults(
    defineProps<{
        cells: HexCells;
        counts: Record<string, number>;
        thresholds: Thresholds;
        points?: App.HexNet.Data.HexNetPointData[];
        bounds?: App.ValueObjects.Coordinate[];
        highlight?: { cell: string; coordinate: App.ValueObjects.Coordinate } | null;
        interactive?: boolean;
        /** With a measure, cells take its color and count only stepping stones with it, markers are filtered. */
        measure?: { id: string; color: string } | null;
        measureCounts?: Record<string, Record<string, number>>;
        markerStyle?: MarkerStyle;
        basemap?: Basemap;
    }>(),
    {
        points: () => [],
        bounds: () => [],
        highlight: null,
        interactive: true,
        measure: null,
        measureCounts: () => ({}),
        markerStyle: 'card',
        basemap: 'soft',
    },
);

const activeCounts = computed<Record<string, number>>(() => (props.measure ? (props.measureCounts[props.measure.id] ?? {}) : props.counts));
const visiblePoints = computed(() =>
    props.measure ? props.points.filter((entry) => entry.point.characteristics.some((c) => c.id === props.measure?.id)) : props.points,
);

defineSlots<{
    cell(props: { cell: CellSelection }): unknown;
    point(props: { point: App.HexNet.Data.HexNetPointData }): unknown;
}>();

const container = ref<HTMLDivElement | null>(null);
const popupContent = document.createElement('div');
const map = shallowRef<L.Map | null>(null);
const selectedCell = ref<CellSelection | null>(null);
const selectedPoint = ref<App.HexNet.Data.HexNetPointData | null>(null);
const isMobile = ref(window.matchMedia('(max-width: 820px)').matches);
const hasSelection = computed(() => selectedCell.value !== null || selectedPoint.value !== null);

let hexLayer: L.GeoJSON | null = null;
let tileLayer: L.TileLayer | null = null;
let pointLayer: L.LayerGroup | null = null;
let highlightLayer: L.Polygon | null = null;
let popup: L.Popup | null = null;
const canvasRenderer = L.canvas({ padding: 0.5 });
const svgRenderer = L.svg();
let resizeObserver: ResizeObserver | null = null;
const mediaQuery = window.matchMedia('(max-width: 820px)');
function onMediaChange(event: MediaQueryListEvent) {
    close();
    isMobile.value = event.matches;
}

function styleFor(feature?: GeoJSON.Feature): L.PathOptions {
    const id = (feature?.properties as { id: string } | undefined)?.id ?? '';
    const level = levelFor(activeCounts.value[id] ?? 0, props.thresholds);
    const measureColor = props.measure && level > 0 ? props.measure.color : null;
    const lighten = (map.value?.getZoom() ?? 0) >= ZOOM_LIGHT_CELLS ? 0.45 : 1;
    return {
        color: '#ffffff',
        weight: 1,
        opacity: 0.85,
        fillColor: measureColor ?? LEVELS[level].color,
        fillOpacity: (measureColor ? MEASURE_OPACITY[level] : LEVELS[level].opacity) * lighten,
    };
}

function select(latlng: L.LatLngExpression, offsetY = 0) {
    if (!map.value) return;
    if (isMobile.value) {
        popup?.remove();
        return;
    }
    popup ??= L.popup({ maxWidth: 320, minWidth: 240, autoPanPadding: [24, 24], className: 'hexnet-popup' });
    popup.options.offset = L.point(0, 7 + offsetY);
    popup.setLatLng(latlng).setContent(popupContent).openOn(map.value);
    nextTick(() => popup?.update());
}

function close() {
    selectedCell.value = null;
    selectedPoint.value = null;
    popup?.remove();
}

function renderCells() {
    if (!map.value) return;
    hexLayer?.remove();
    hexLayer = L.geoJSON(props.cells, {
        style: (feature) => ({ ...styleFor(feature), renderer: canvasRenderer }),
        interactive: props.interactive,
        onEachFeature: (feature, layer) => {
            if (!props.interactive) return;
            layer.on('click', (event: L.LeafletMouseEvent) => {
                const id = (feature.properties as { id: string }).id;
                const count = activeCounts.value[id] ?? 0;
                selectedPoint.value = null;
                selectedCell.value = {
                    id,
                    count,
                    level: levelFor(count, props.thresholds),
                    center: cellCenter(feature as GeoJSON.Feature<GeoJSON.Polygon>),
                };
                select(event.latlng);
            });
        },
    }).addTo(map.value);
    renderHighlight();
}

function renderPoints() {
    if (!map.value) return;
    pointLayer?.clearLayers();
    pointLayer ??= L.layerGroup().addTo(map.value);
    const zoom = map.value.getZoom();
    for (const entry of visiblePoints.value) {
        const measures = entry.point.characteristics.map((c) => ({ id: c.id, color: c.color ?? '#7d8d85', icon: c.icon_path, name: c.name }));
        const marker = markerFor(measures, zoom, props.markerStyle);
        if (!marker) return;
        const icon = L.divIcon({ html: marker.html, className: 'hexnet-lolly', iconSize: marker.size, iconAnchor: marker.anchor });
        L.marker([entry.point.coordinate.lat, entry.point.coordinate.lng], { icon, riseOnHover: true })
            .on('click', () => {
                selectedCell.value = null;
                selectedPoint.value = entry;
                select([entry.point.coordinate.lat, entry.point.coordinate.lng], marker.popupOffset);
            })
            .addTo(pointLayer);
    }
}

function renderTiles() {
    if (!map.value) return;
    tileLayer?.remove();
    const basemap = BASEMAPS[props.basemap];
    tileLayer = L.tileLayer(basemap.url, {
        attribution: basemap.attribution,
        subdomains: basemap.subdomains,
        maxZoom: 19,
        className: basemap.className,
    }).addTo(map.value);
}

function renderHighlight() {
    highlightLayer?.remove();
    highlightLayer = null;
    if (!map.value || !props.highlight) return;
    const feature = props.cells.features.find((f) => f.properties.id === props.highlight?.cell);
    if (!feature) return;
    const latlngs = feature.geometry.coordinates[0].map(([lng, lat]) => L.latLng(lat, lng));
    highlightLayer = L.polygon(latlngs, {
        renderer: svgRenderer,
        className: 'hexnet-pulse',
        color: '#e8720c',
        weight: 3,
        fillColor: '#e8720c',
        fillOpacity: 0.25,
        interactive: false,
    }).addTo(map.value);
}

/** A view given as #map=zoom/lat/lng, like on openstreetmap.org, wins over fitting the play area. */
function viewFromHash(): { zoom: number; lat: number; lng: number } | null {
    const match = window.location.hash.match(/map=([\d.]+)\/(-?[\d.]+)\/(-?[\d.]+)/);
    if (!match) return null;
    const view = { zoom: Math.min(19, Math.max(3, Number(match[1]))), lat: Number(match[2]), lng: Number(match[3]) };
    const valid = [view.zoom, view.lat, view.lng].every(Number.isFinite) && Math.abs(view.lat) <= 90 && Math.abs(view.lng) <= 180;
    return valid ? view : null;
}

function fit() {
    if (!map.value) return;
    const view = props.interactive ? viewFromHash() : null;
    if (view) {
        map.value.setView([view.lat, view.lng], view.zoom);
        return;
    }
    if (props.bounds.length === 2) {
        map.value.fitBounds(
            [
                [props.bounds[0].lat, props.bounds[0].lng],
                [props.bounds[1].lat, props.bounds[1].lng],
            ],
            { padding: [20, 20] },
        );
    } else if (props.cells.features.length > 0) {
        map.value.fitBounds(L.geoJSON(props.cells).getBounds(), { padding: [20, 20] });
    } else {
        map.value.setView([49.8728, 8.6512], 13);
    }
}

function flyTo(coordinate: App.ValueObjects.Coordinate, zoom = 16) {
    map.value?.flyTo([coordinate.lat, coordinate.lng], zoom, { duration: 1.2 });
}

onMounted(() => {
    if (!container.value) return;
    map.value = L.map(container.value, { preferCanvas: true, zoomSnap: 0.25, zoomDelta: 1 });
    map.value.setMaxZoom(19);
    renderTiles();
    fit();
    renderCells();
    renderPoints();
    map.value.on('zoomend', () => {
        renderPoints();
        hexLayer?.setStyle(styleFor);
    });
    map.value.on('popupclose', () => {
        if (!isMobile.value) {
            selectedCell.value = null;
            selectedPoint.value = null;
        }
    });
    mediaQuery.addEventListener('change', onMediaChange);
    resizeObserver = new ResizeObserver(() => map.value?.invalidateSize());
    resizeObserver.observe(container.value);
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    mediaQuery.removeEventListener('change', onMediaChange);
    map.value?.remove();
});

watch(
    () => props.cells,
    () => {
        renderCells();
        fit();
    },
);
watch(
    () => [activeCounts.value, props.thresholds, props.measure?.id, props.measure?.color],
    () => {
        close();
        hexLayer?.setStyle(styleFor);
    },
    { deep: true },
);
watch(() => [visiblePoints.value, props.markerStyle], renderPoints);
watch(() => props.basemap, renderTiles);
watch(() => props.highlight, renderHighlight);

defineExpose({ flyTo, close, invalidateSize: () => map.value?.invalidateSize() });
</script>

<template>
    <div class="relative h-full w-full">
        <div ref="container" class="hexnet-map h-full w-full" />
        <Teleport v-if="!isMobile && hasSelection" :to="popupContent">
            <slot v-if="selectedCell" name="cell" :cell="selectedCell" />
            <slot v-else-if="selectedPoint" name="point" :point="selectedPoint" />
        </Teleport>
        <Transition name="hexnet-sheet">
            <div
                v-if="isMobile && hasSelection"
                class="absolute inset-x-0 bottom-0 z-[1100] max-h-[70%] overflow-y-auto rounded-t-2xl bg-white p-4 pb-6 shadow-[0_-8px_24px_rgba(0,0,0,.18)]"
            >
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-gray-300" />
                <button class="absolute top-3 right-3 rounded-full p-1 text-gray-500 hover:bg-gray-100" aria-label="Schließen" @click="close">
                    <X class="h-5 w-5" />
                </button>
                <slot v-if="selectedCell" name="cell" :cell="selectedCell" />
                <slot v-else-if="selectedPoint" name="point" :point="selectedPoint" />
            </div>
        </Transition>
    </div>
</template>

<style>
.hexnet-map {
    background: #eaeae6;
    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        Roboto,
        sans-serif;
}
.hexnet-tiles-muted {
    filter: grayscale(0.85) brightness(1.09) contrast(0.86) opacity(0.75);
}
.hexnet-tiles-soft {
    filter: saturate(0.55) brightness(1.05) contrast(0.9);
}
.hexnet-tiles-hot {
    filter: saturate(0.75) brightness(1.04);
}
.hexnet-lolly {
    background: none;
    border: none;
    filter: drop-shadow(0 1px 1.5px rgba(0, 0, 0, 0.35));
}
.hexnet-popup .leaflet-popup-content {
    margin: 13px 15px;
    font-size: 12.5px;
    line-height: 1.5;
}
.hexnet-pulse {
    animation: hexnet-pulse 1.4s ease-in-out infinite;
    transform-box: fill-box;
    transform-origin: center;
}
@keyframes hexnet-pulse {
    0%,
    100% {
        stroke-opacity: 1;
        fill-opacity: 0.15;
    }
    50% {
        stroke-opacity: 0.35;
        fill-opacity: 0.5;
    }
}
.hexnet-sheet-enter-active,
.hexnet-sheet-leave-active {
    transition: transform 0.2s ease;
}
.hexnet-sheet-enter-from,
.hexnet-sheet-leave-to {
    transform: translateY(100%);
}
</style>
