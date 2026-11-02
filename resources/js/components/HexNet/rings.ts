import L from 'leaflet';
import { LEVELS, levelFor, type Thresholds } from './hexnet';

export type RingWidth = 'equal' | 'count';

export type RingMeasure = { id: string; color: string };

/** Share of the cell radius: rings run from OUTER to CORE, the core shows the level of the cell. */
const OUTER = 0.94;
const CORE = 0.26;
const GAP = 0.03;

type LngLat = [number, number];

function scaled(ring: LngLat[], center: LngLat, factor: number): L.LatLngExpression[] {
    return ring.map(([lng, lat]) => [center[1] + (lat - center[1]) * factor, center[0] + (lng - center[0]) * factor]);
}

/**
 * Concentric hexagons, one per measure present in the cell, outermost the measure most common in the whole campaign.
 * Only present measures get a ring, so cells with one measure stay calm and neighbours form continuous colour areas.
 */
export function ringLayers(
    feature: GeoJSON.Feature<GeoJSON.Polygon, { id: string }>,
    countsByMeasure: Record<string, number>,
    order: RingMeasure[],
    options: { width: RingWidth; thresholds: Thresholds; focus: string | null; renderer: L.Renderer; stones: number },
): L.Polygon[] {
    const present = order.filter((m) => (countsByMeasure[m.id] ?? 0) > 0);
    if (present.length === 0) return [];

    const ring = feature.geometry.coordinates[0].slice(0, -1) as LngLat[];
    const center: LngLat = [ring.reduce((s, c) => s + c[0], 0) / ring.length, ring.reduce((s, c) => s + c[1], 0) / ring.length];
    const weights = present.map((m) => (options.width === 'count' ? countsByMeasure[m.id] : 1));
    const total = weights.reduce((a, b) => a + b, 0);
    const span = OUTER - CORE;
    const style = { renderer: options.renderer, interactive: false, lineJoin: 'round' as const, weight: 2 };
    const layers: L.Polygon[] = [];

    let outer = OUTER;
    present.forEach((measure, i) => {
        const width = (span * weights[i]) / total;
        const inner = Math.max(CORE, outer - width + Math.min(GAP, width * 0.3));
        const faded = options.focus !== null && options.focus !== measure.id;
        layers.push(
            L.polygon([scaled(ring, center, outer), scaled(ring, center, inner)], {
                ...style,
                color: measure.color,
                opacity: faded ? 0.15 : 0.9,
                fillColor: measure.color,
                fillOpacity: faded ? 0.12 : 0.85,
            }),
        );
        outer -= width;
    });

    const level = levelFor(options.stones, options.thresholds);
    layers.push(
        L.polygon(scaled(ring, center, CORE - 0.02), {
            ...style,
            color: '#ffffff',
            weight: 1.5,
            opacity: 1,
            fillColor: LEVELS[level].color,
            fillOpacity: 1,
        }),
    );

    return layers;
}
