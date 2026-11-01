export type HexCells = GeoJSON.FeatureCollection<GeoJSON.Polygon, { id: string }>;

export type Thresholds = { medium: number; dark: number };

export const LEVELS = [
    { name: 'Noch offen', color: '#e4e9e2', opacity: 0.34 },
    { name: 'Hellgrün', color: '#b7e4a8', opacity: 0.62 },
    { name: 'Mittelgrün', color: '#52b45a', opacity: 0.66 },
    { name: 'Dunkelgrün', color: '#1f7a34', opacity: 0.72 },
] as const;

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

export function cellCenter(feature: GeoJSON.Feature<GeoJSON.Polygon>): { lat: number; lng: number } {
    const ring = feature.geometry.coordinates[0].slice(0, -1);
    const lng = ring.reduce((sum, c) => sum + c[0], 0) / ring.length;
    const lat = ring.reduce((sum, c) => sum + c[1], 0) / ring.length;
    return { lat, lng };
}

export function lollySvg(colors: string[], size: number): string {
    const r = size / 2;
    const stick = Math.round(size * 0.6);
    const segments = colors.length === 0 ? ['#7d8d85'] : colors;
    const step = (2 * Math.PI) / segments.length;
    const paths = segments
        .map((color, i) => {
            if (segments.length === 1) {
                return `<circle cx="${r}" cy="${r}" r="${r - 1}" fill="${color}"/>`;
            }
            const a0 = i * step - Math.PI / 2;
            const a1 = a0 + step;
            const x0 = r + (r - 1) * Math.cos(a0);
            const y0 = r + (r - 1) * Math.sin(a0);
            const x1 = r + (r - 1) * Math.cos(a1);
            const y1 = r + (r - 1) * Math.sin(a1);
            const large = step > Math.PI ? 1 : 0;
            return `<path d="M${r},${r} L${x0.toFixed(2)},${y0.toFixed(2)} A${r - 1},${r - 1} 0 ${large} 1 ${x1.toFixed(2)},${y1.toFixed(2)} Z" fill="${color}"/>`;
        })
        .join('');
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size + stick}" viewBox="0 0 ${size} ${size + stick}">
<line x1="${r}" y1="${r}" x2="${r}" y2="${size + stick - 1}" stroke="#4c5b51" stroke-width="${Math.max(1.5, size / 12)}" stroke-linecap="round"/>
${paths}<circle cx="${r}" cy="${r}" r="${r - 1}" fill="none" stroke="#ffffff" stroke-width="1.5"/><circle cx="${r}" cy="${r}" r="${Math.max(1.5, size / 9)}" fill="#ffffff"/></svg>`;
}
