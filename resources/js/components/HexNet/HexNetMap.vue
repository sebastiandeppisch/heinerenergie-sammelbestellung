<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, X } from '@lucide/vue';
import './hexnet.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import {
    BASEMAPS,
    type Basemap,
    cellCenter,
    DEFAULT_LEVEL_COLORS,
    type HexCells,
    LEVELS,
    levelFor,
    MEASURE_OPACITY,
    type MarkerStyle,
    type Thresholds,
    ZOOM_LIGHT_CELLS,
} from './hexnet';
import { markerFor, ZOOM_SMALL } from './markers';
import { type RingMeasure, ringLayers, type RingWidth } from './rings';

/** count is in the unit of the scoring mode, stones the stepping stones of the cell. */
export type CellSelection = { id: string; count: number; stones: number; level: number; center: { lat: number; lng: number } };

const props = withDefaults(
    defineProps<{
        cells: HexCells;
        counts: Record<string, number>;
        /** Stepping stones per cell when counts are in another unit. */
        stoneCounts?: Record<string, number> | null;
        thresholds: Thresholds;
        /** Thresholds of the overall score, for the core of the rings when the cells show a measure. */
        scoreThresholds?: Thresholds | null;
        points?: App.HexNet.Data.HexNetPointData[];
        bounds?: App.ValueObjects.Coordinate[];
        highlight?: { cell: string; coordinate: App.ValueObjects.Coordinate } | null;
        interactive?: boolean;
        /** With a measure, cells take its color and count only stepping stones with it, markers are filtered. */
        measure?: { id: string; color: string; icon?: string | null } | null;
        measureCounts?: Record<string, Record<string, number>>;
        markerStyle?: MarkerStyle;
        basemap?: Basemap;
        /** Rings: concentric hexagons per measure instead of the green levels. */
        cellStyle?: 'levels' | 'rings';
        ringWidth?: RingWidth;
        ringOrder?: RingMeasure[];
        levelColors?: string[];
    }>(),
    {
        points: () => [],
        bounds: () => [],
        stoneCounts: null,
        scoreThresholds: null,
        highlight: null,
        interactive: true,
        measure: null,
        measureCounts: () => ({}),
        markerStyle: 'card',
        basemap: 'soft',
        cellStyle: 'levels',
        ringWidth: 'equal',
        ringOrder: () => [],
        levelColors: () => DEFAULT_LEVEL_COLORS,
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
const currentZoom = ref(0);
/** Set once the visitor zoomed in beyond the first view, then the hint to zoom in is gone for good. */
const zoomedIn = ref(false);
let initialZoom = Infinity;
/** Shown stepping stones per cell, the ones a visitor finds when zooming in. */
const shownByCell = computed(() => {
    const byCell: Record<string, number> = {};
    for (const entry of visiblePoints.value) byCell[entry.cell] = (byCell[entry.cell] ?? 0) + 1;
    return byCell;
});
const showZoomHint = computed(() => props.interactive && !zoomedIn.value && visiblePoints.value.length > 0 && currentZoom.value < ZOOM_SMALL);
const hasSelection = computed(() => selectedCell.value !== null || selectedPoint.value !== null);

let hexLayer: L.GeoJSON | null = null;
let tileLayer: L.TileLayer | null = null;
let ringLayer: L.LayerGroup | null = null;
let cellIconLayer: L.LayerGroup | null = null;
let ownPosition: L.LayerGroup | null = null;
const locateMessage = ref<string | null>(null);

/** Flies to the visitor's position. The position stays in the browser. */
function locate() {
    if (!navigator.geolocation) {
        showLocateMessage('Dein Browser kann den Standort nicht bestimmen.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
            if (!map.value) return;
            const latlng = L.latLng(coords.latitude, coords.longitude);
            ownPosition?.remove();
            ownPosition = L.layerGroup([
                L.circle(latlng, {
                    radius: coords.accuracy,
                    renderer: svgRenderer,
                    color: '#1a73e8',
                    weight: 1,
                    fillOpacity: 0.1,
                    interactive: false,
                }),
                L.circleMarker(latlng, {
                    radius: 7,
                    renderer: svgRenderer,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#1a73e8',
                    fillOpacity: 1,
                    interactive: false,
                }),
            ]).addTo(map.value);
            map.value.flyTo(latlng, Math.max(map.value.getZoom(), 16), { duration: 1 });
        },
        () => showLocateMessage('Standort nicht verfügbar. Bitte erlaube den Zugriff auf deinen Standort.'),
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

function showLocateMessage(message: string) {
    locateMessage.value = message;
    setTimeout(() => (locateMessage.value = null), 4000);
}

const LocateControl = L.Control.extend({
    onAdd() {
        const button = L.DomUtil.create('button', 'leaflet-bar hexnet-locate');
        button.type = 'button';
        button.title = 'Zu meinem Standort';
        button.setAttribute('aria-label', 'Zu meinem Standort');
        button.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" x2="5" y1="12" y2="12"/><line x1="19" x2="22" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="5"/><line x1="12" x2="12" y1="19" y2="22"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="3"/></svg>';
        L.DomEvent.disableClickPropagation(button);
        L.DomEvent.on(button, 'click', locate);
        return button;
    },
});

/** Counts per cell and measure, turned around from the counts per measure and cell. */
const countsByCell = computed(() => {
    const byCell: Record<string, Record<string, number>> = {};
    for (const [measureId, cells] of Object.entries(props.measureCounts)) {
        for (const [cell, count] of Object.entries(cells)) {
            (byCell[cell] ??= {})[measureId] = count;
        }
    }
    return byCell;
});
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
    if (props.cellStyle === 'rings') {
        return {
            color: '#ffffff',
            weight: 1,
            opacity: 0.85,
            fillColor: props.levelColors[0],
            fillOpacity: (id in countsByCell.value ? 0.15 : LEVELS[0].opacity) * lighten,
        };
    }
    return {
        color: '#ffffff',
        weight: 1,
        opacity: 0.85,
        fillColor: measureColor ?? props.levelColors[level],
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

function stonesIn(id: string): number {
    if (props.measure || !props.stoneCounts) return activeCounts.value[id] ?? 0;
    return props.stoneCounts[id] ?? 0;
}

/** In the overview a cell with stepping stones invites to zoom in: a click flies there, then shows the cell. */
function openCell(feature: GeoJSON.Feature<GeoJSON.Polygon>, latlng: L.LatLng) {
    const id = (feature.properties as { id: string }).id;
    const count = activeCounts.value[id] ?? 0;
    const center = cellCenter(feature);
    const cell = { id, count, stones: stonesIn(id), level: levelFor(count, props.thresholds), center };
    const show = (at: L.LatLngExpression) => {
        selectedPoint.value = null;
        selectedCell.value = cell;
        select(at);
    };

    if (!map.value || (shownByCell.value[id] ?? 0) === 0 || map.value.getZoom() >= ZOOM_SMALL) {
        show(latlng);
        return;
    }
    close();
    const target = L.latLng(center.lat, center.lng);
    const ticket = ++flight;
    map.value.once('moveend', () => {
        // The visitor may have dragged or zoomed elsewhere during the flight, then the cell is not what they look at.
        if (ticket === flight && map.value && map.value.getCenter().distanceTo(target) < 30) show(target);
    });
    map.value.flyTo([center.lat, center.lng], ZOOM_SMALL + 1, { duration: 0.9 });
}

let hoverTooltip: L.Tooltip | null = null;
let flight = 0;

/** In the overview, hovering a cell with stepping stones tells how many there are to discover. */
function showZoomTooltip(id: string, latlng: L.LatLng) {
    hideZoomTooltip();
    const stones = shownByCell.value[id] ?? 0;
    if (!map.value || isMobile.value || stones === 0 || map.value.getZoom() >= ZOOM_SMALL) return;
    hoverTooltip = L.tooltip({ direction: 'top', offset: [0, -8], className: 'hexnet-cell-tooltip' })
        .setLatLng(latlng)
        .setContent(`${stones} ${stones === 1 ? 'Trittstein' : 'Trittsteine'} – zoom rein`)
        .addTo(map.value);
}

function hideZoomTooltip() {
    hoverTooltip?.remove();
    hoverTooltip = null;
}

function zoomInHint() {
    map.value?.flyTo(map.value.getCenter(), Math.max(ZOOM_SMALL, map.value.getZoom() + 2), { duration: 0.9 });
}

function renderCells() {
    if (!map.value) return;
    hexLayer?.remove();
    hexLayer = L.geoJSON(props.cells, {
        style: (feature) => ({ ...styleFor(feature), renderer: canvasRenderer }),
        interactive: props.interactive,
        onEachFeature: (feature, layer) => {
            if (!props.interactive) return;
            layer.on('click', (event: L.LeafletMouseEvent) => openCell(feature as GeoJSON.Feature<GeoJSON.Polygon>, event.latlng));
            layer.on('mouseover', (event: L.LeafletMouseEvent) => showZoomTooltip((feature.properties as { id: string }).id, event.latlng));
            layer.on('mousemove', (event: L.LeafletMouseEvent) => hoverTooltip?.setLatLng(event.latlng));
            layer.on('mouseout', hideZoomTooltip);
        },
    }).addTo(map.value);
    renderRings();
    renderCellIcons();
    renderHighlight();
}

/** With a measure shown, its icon marks every cell that has it. */
function renderCellIcons() {
    cellIconLayer?.remove();
    cellIconLayer = null;
    const icon = props.measure?.icon;
    if (!map.value || !props.measure || !icon) return;
    cellIconLayer = L.layerGroup().addTo(map.value);
    const html = `<span class="hexnet-cell-icon" style="--c:${props.measure.color}"><img src="${icon}" alt=""></span>`;
    for (const feature of props.cells.features) {
        if ((activeCounts.value[feature.properties.id] ?? 0) === 0) continue;
        const center = cellCenter(feature);
        L.marker([center.lat, center.lng], {
            icon: L.divIcon({ html, className: 'hexnet-cell-icon-wrap', iconSize: [22, 22], iconAnchor: [11, 11] }),
            interactive: false,
            keyboard: false,
        }).addTo(cellIconLayer);
    }
}

function renderRings() {
    ringLayer?.remove();
    ringLayer = null;
    if (!map.value || props.cellStyle !== 'rings') return;
    ringLayer = L.layerGroup().addTo(map.value);
    const lighten = map.value.getZoom() >= ZOOM_LIGHT_CELLS;
    for (const feature of props.cells.features) {
        const counts = countsByCell.value[feature.properties.id];
        if (!counts) continue;
        for (const layer of ringLayers(feature, counts, props.ringOrder, {
            width: props.ringWidth,
            thresholds: props.scoreThresholds ?? props.thresholds,
            focus: props.measure?.id ?? null,
            renderer: canvasRenderer,
            stones: props.counts[feature.properties.id] ?? 0,
            levelColors: props.levelColors,
        })) {
            if (lighten) layer.setStyle({ fillOpacity: (layer.options.fillOpacity ?? 1) * 0.5, opacity: (layer.options.opacity ?? 1) * 0.6 });
            layer.addTo(ringLayer);
        }
    }
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
        if (!marker.interactive) {
            L.marker([entry.point.coordinate.lat, entry.point.coordinate.lng], { icon, interactive: false, keyboard: false }).addTo(pointLayer);
            continue;
        }
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
    if (props.interactive) new LocateControl({ position: 'topleft' }).addTo(map.value);
    renderTiles();
    fit();
    currentZoom.value = initialZoom = map.value.getZoom();
    renderCells();
    renderPoints();
    map.value.on('zoomstart', hideZoomTooltip);
    map.value.on('zoomend', () => {
        currentZoom.value = map.value?.getZoom() ?? 0;
        if (currentZoom.value > initialZoom + 0.1) zoomedIn.value = true;
        renderPoints();
        hexLayer?.setStyle(styleFor);
        renderRings();
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
    () => [activeCounts.value, props.thresholds, props.measure?.id, props.measure?.color, props.levelColors],
    () => {
        close();
        hexLayer?.setStyle(styleFor);
        renderRings();
        renderCellIcons();
    },
    { deep: true },
);
watch(
    () => [props.cellStyle, props.ringWidth],
    () => {
        hexLayer?.setStyle(styleFor);
        renderRings();
    },
);
watch(() => [visiblePoints.value, props.markerStyle], renderPoints);
watch(() => props.basemap, renderTiles);
watch(() => props.highlight, renderHighlight);

defineExpose({ flyTo, close, invalidateSize: () => map.value?.invalidateSize() });
</script>

<template>
    <div class="relative h-full w-full">
        <div ref="container" class="hexnet-map h-full w-full" />
        <div v-if="locateMessage" class="hexnet-toast" role="status">{{ locateMessage }}</div>
        <button v-else-if="showZoomHint" type="button" class="hexnet-zoom-hint" data-test="hexnet-zoom-hint" @click="zoomInHint">
            <Search class="h-4 w-4" />
            Zoom rein und entdecke {{ visiblePoints.length }} {{ visiblePoints.length === 1 ? 'Garten' : 'Gärten' }}
        </button>
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
.hexnet-locate {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: #fff;
    color: #16211c;
    cursor: pointer;
}
.hexnet-locate:hover {
    background: #f4f4f4;
}
.hexnet-toast {
    position: absolute;
    left: 50%;
    top: 12px;
    z-index: 1100;
    transform: translateX(-50%);
    max-width: 90%;
    padding: 8px 12px;
    border-radius: 8px;
    background: #16211c;
    color: #fff;
    font-size: 12.5px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}
.hexnet-zoom-hint {
    position: absolute;
    left: 50%;
    top: 12px;
    z-index: 1000;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transform: translateX(-50%);
    padding: 8px 14px;
    border: 0;
    border-radius: 999px;
    background: #e8720c;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(232, 114, 12, 0.4);
    animation: hexnet-hint-bob 2.4s ease-in-out infinite;
}
.hexnet-zoom-hint:hover {
    background: #cf6408;
}
@keyframes hexnet-hint-bob {
    0%,
    100% {
        transform: translate(-50%, 0);
    }
    50% {
        transform: translate(-50%, 3px);
    }
}
@media (prefers-reduced-motion: reduce) {
    .hexnet-zoom-hint {
        animation: none;
    }
}
.hexnet-map .leaflet-control-zoom {
    border: none;
    box-shadow: 0 2px 8px rgba(22, 33, 28, 0.28);
}
.hexnet-map .leaflet-control-zoom a {
    width: 40px;
    height: 40px;
    line-height: 38px;
    font-size: 24px;
    font-weight: 700;
    color: #1f7a34;
}
.hexnet-map .leaflet-control-zoom-in {
    background: #1f7a34;
    color: #fff !important;
}
.hexnet-map .leaflet-control-zoom-in:hover {
    background: #2b8f43;
}
.hexnet-map .leaflet-control-zoom-in.leaflet-disabled {
    background: #f4f4f4;
    color: #bbb !important;
}
.hexnet-cell-tooltip {
    padding: 4px 8px;
    border: none;
    border-radius: 6px;
    background: #16211c;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}
.hexnet-cell-tooltip::before {
    border-top-color: #16211c;
}
.hexnet-cell-icon-wrap {
    background: none;
    border: none;
}
.hexnet-cell-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: #fff;
    border: 2px solid var(--c);
    box-shadow: 0 1px 2px rgba(22, 33, 28, 0.3);
}
.hexnet-cell-icon img {
    width: 13px;
    height: 13px;
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
