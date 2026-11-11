export type HexCells = GeoJSON.FeatureCollection<GeoJSON.Polygon, { id: string }>;

export type Thresholds = { medium: number; dark: number };

export const LEVELS = [
    { name: 'Noch offen', color: '#e4e9e2', opacity: 0.3 },
    { name: 'Hellgrün', color: '#b7e4a8', opacity: 0.72 },
    { name: 'Mittelgrün', color: '#52b45a', opacity: 0.74 },
    { name: 'Dunkelgrün', color: '#1f7a34', opacity: 0.8 },
] as const;

/** Level colors when a HexaNetz does not bring its own: open, light, medium, dark. */
export const DEFAULT_LEVEL_COLORS = LEVELS.map((level) => level.color);

export const SIGNAL = '#e8720c';

export function levelFor(count: number, t: Thresholds): number {
    if (count >= t.dark) return 3;
    if (count >= t.medium) return 2;
    if (count >= 1) return 1;
    return 0;
}

export function missingToNextLevel(count: number, t: Thresholds): { missing: number; next: string } | null {
    const level = levelFor(count, t);
    const targets = [1, t.medium, t.dark];
    if (level >= 3) return null;
    return { missing: targets[level] - count, next: LEVELS[level + 1].name };
}

export function steppingStones(n: number): string {
    return n === 1 ? 'Trittstein' : 'Trittsteine';
}

export type ScoringMode = App.HexNet.Enums.HexNetScoringMode;

export const SCORING_MODES: Record<ScoringMode, { label: string; description: string }> = {
    stones: { label: 'Trittsteine', description: 'Jeder Garten zählt eins, egal wie viele Maßnahmen er hat.' },
    measures: { label: 'Maßnahmen', description: 'Jede Maßnahme jedes Gartens zählt. Ein Garten mit drei Maßnahmen zählt drei.' },
    distinct_measures: {
        label: 'Verschiedene Maßnahmen',
        description: 'Zählt, wie viele verschiedene Maßnahmen es in der Wabe gibt. Dunkelgrün heißt dann: alle Maßnahmen erfüllt.',
    },
};

/** The unit a cell counts in, e.g. "3 Maßnahmen". */
export function scoreUnit(n: number, mode: ScoringMode): string {
    if (mode === 'stones') return steppingStones(n);
    const measures = n === 1 ? 'Maßnahme' : 'Maßnahmen';
    return mode === 'measures' ? measures : `verschiedene ${measures}`;
}

/** The unit after "ab", e.g. "ab 3 verschiedenen Maßnahmen". */
export function scoreUnitDative(n: number, mode: ScoringMode): string {
    if (mode === 'stones') return n === 1 ? 'Trittstein' : 'Trittsteinen';
    const measures = n === 1 ? 'Maßnahme' : 'Maßnahmen';
    return mode === 'measures' ? measures : `verschiedenen ${measures}`;
}

/** Thresholds a new scoring mode starts with, with distinct measures dark means all measures. */
export function defaultThresholds(mode: ScoringMode, measureCount: number): Thresholds {
    if (mode === 'measures') return { medium: 3, dark: 6 };
    if (mode === 'distinct_measures') {
        const dark = Math.max(2, measureCount);
        return { medium: Math.max(1, Math.min(dark - 1, Math.ceil(dark / 2))), dark };
    }
    return { medium: 2, dark: 3 };
}

export function cellCenter(feature: GeoJSON.Feature<GeoJSON.Polygon>): { lat: number; lng: number } {
    const ring = feature.geometry.coordinates[0].slice(0, -1);
    const lng = ring.reduce((sum, c) => sum + c[0], 0) / ring.length;
    const lat = ring.reduce((sum, c) => sum + c[1], 0) / ring.length;
    return { lat, lng };
}

export type MarkerStyle = 'card' | 'wheel';

export type Basemap = 'soft' | 'muted' | 'osmde' | 'hot';

export const BASEMAPS: Record<Basemap, { label: string; url: string; subdomains: string; attribution: string; className: string }> = {
    soft: {
        label: 'OSM, sanft gedämpft',
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        subdomains: 'abc',
        attribution: '&copy; OpenStreetMap',
        className: 'hexnet-tiles-soft',
    },
    muted: {
        label: 'OSM, entsättigt',
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        subdomains: 'abc',
        attribution: '&copy; OpenStreetMap',
        className: 'hexnet-tiles-muted',
    },
    osmde: {
        label: 'OSM Deutschland',
        url: 'https://tile.openstreetmap.de/{z}/{x}/{y}.png',
        subdomains: 'abc',
        attribution: '&copy; OpenStreetMap',
        className: 'hexnet-tiles-soft',
    },
    hot: {
        label: 'OSM Humanitarian',
        url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
        subdomains: 'abc',
        attribution: '&copy; OpenStreetMap, Tiles HOT/OSM France',
        className: 'hexnet-tiles-hot',
    },
};

/** From this zoom the cells get lighter, so streets and gardens below stay visible. */
export const ZOOM_LIGHT_CELLS = 16.5;

/** Fill of a cell when only one measure is shown: its color, stronger with more stepping stones. */
export const MEASURE_OPACITY = [0, 0.3, 0.55, 0.8] as const;
