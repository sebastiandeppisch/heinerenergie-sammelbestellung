import type { MarkerStyle } from './hexnet';

export type MarkerMeasure = { id: string; color: string; icon: string | null; name: string };

/** Mini markers are only a hint that there are gardens to discover, they take no clicks. */
export type MarkerIcon = { html: string; size: [number, number]; anchor: [number, number]; popupOffset: number; interactive: boolean };

/**
 * Zoom from which mini markers are shown, from which full markers are shown, from which they are larger and from which
 * they show the icons. Mini markers already appear in the city overview.
 */
export const ZOOM_MINI = 10.5;
export const ZOOM_SMALL = 14.75;
export const ZOOM_LARGE = 16.75;
export const ZOOM_DETAIL = 18;

const INK = '#16211c';
const STEM = '#1f7a34';
const LEAF = '#52b45a';
const SHADOW = 'filter:drop-shadow(0 1px 2px rgba(22,33,28,.35))';
const NEUTRAL = '#7d8d85';

export function markerFor(measures: MarkerMeasure[], zoom: number, style: MarkerStyle): MarkerIcon | null {
    const sorted = [...measures].sort((a, b) => a.id.localeCompare(b.id));
    if (zoom < ZOOM_MINI) return null;
    if (zoom < ZOOM_SMALL) return mini(zoom < 12.5 ? 9 : 12);
    if (zoom < ZOOM_LARGE) return bud(sorted, 18);
    if (zoom < ZOOM_DETAIL) return bud(sorted, 28);
    return style === 'card' ? leafCard(sorted) : flowerWheel(sorted);
}

/** A small lollipop without segments: a green head on a short stem. */
export function mini(size: number): MarkerIcon {
    const height = size * 1.6;
    const html = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${height}" viewBox="0 0 10 16">
<path d="M5 8.5V15.4" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/><path d="M5 8.5V15.4" stroke="${STEM}" stroke-width="1.2" stroke-linecap="round"/>
<circle cx="5" cy="5" r="4.2" fill="${LEAF}" stroke="#fff" stroke-width="1.4"/><circle cx="5" cy="5" r="1.5" fill="${STEM}"/></svg>`;
    return { html, size: [size, height], anchor: [size / 2, height - 0.5], popupOffset: -height, interactive: false };
}

/** "Knospe": a white button with a segmented ring in the measure colors on a stem with a leaf. */
export function bud(measures: MarkerMeasure[], size: number): MarkerIcon {
    const colors = measures.length === 0 ? [NEUTRAL] : measures.map((m) => m.color);
    const n = colors.length;
    const seg = 100 / n;
    const gap = n === 1 ? 0 : Math.min(4, seg * 0.25);
    const ring = colors
        .map(
            (color, i) =>
                `<circle cx="12" cy="12" r="7.5" pathLength="100" stroke="${color}" stroke-dasharray="${(seg - gap).toFixed(2)} ${(100 - seg + gap).toFixed(2)}" stroke-dashoffset="${(-(i * seg + gap / 2)).toFixed(2)}"/>`,
        )
        .join('');
    const height = size * 1.5;
    const html = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${height}" viewBox="0 0 24 36" style="${SHADOW}">
<ellipse cx="12" cy="34.6" rx="3.2" ry="1.1" fill="${INK}" opacity=".25"/>
<path d="M12 22.5V34.4" stroke="#fff" stroke-width="3" stroke-linecap="round"/><path d="M12 22.5V34.4" stroke="${STEM}" stroke-width="1.6" stroke-linecap="round"/>
<path d="M12 30.2c2.6-.3 4.2-1.9 4.5-4.2-2.6.2-4.2 1.7-4.5 4.2z" fill="${LEAF}"/>
<circle cx="12" cy="12" r="11" fill="#fff" stroke="${INK}" stroke-opacity=".18"/>
<g transform="rotate(-90 12 12)" fill="none" stroke-width="4">${ring}</g>
<circle cx="12" cy="12" r="2.6" fill="${STEM}"/></svg>`;
    return { html, size: [size, height], anchor: [size / 2, (height * 35) / 36], popupOffset: -height + 4, interactive: true };
}

function badge(m: MarkerMeasure, cx: number, cy: number): string {
    const icon = m.icon ? `<image href="${m.icon}" x="${cx - 7}" y="${cy - 7}" width="14" height="14"/>` : '';
    return `<g><title>${escapeXml(m.name)}</title><circle cx="${cx}" cy="${cy}" r="14" fill="${m.color}"/><circle cx="${cx}" cy="${cy}" r="10.5" fill="#fff"/>${icon}</g>`;
}

/** "Blätter-Card": a speech bubble on the bud's stem with one badge per measure, beyond four as three plus "+n". */
export function leafCard(measures: MarkerMeasure[]): MarkerIcon {
    const list = measures.length === 0 ? [{ id: '', color: NEUTRAL, icon: null, name: '' }] : measures;
    const shown = list.length > 4 ? list.slice(0, 3) : list;
    const overflow = list.length - shown.length;
    const slots = shown.length + (overflow > 0 ? 1 : 0);
    const w = 12 + 28 * slots + 4 * (slots - 1) + 4;
    const cx = w / 2;
    const badges = shown.map((m, i) => badge(m, 22 + 32 * i, 22)).join('');
    const more =
        overflow > 0
            ? `<circle cx="${22 + 32 * shown.length}" cy="22" r="14" fill="#e4e9e2"/><text x="${22 + 32 * shown.length}" y="22" font-family="system-ui,sans-serif" font-size="12" font-weight="700" fill="${INK}" text-anchor="middle" dominant-baseline="central">+${overflow}</text>`
            : '';
    const html = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="64" viewBox="0 0 ${w} 64" style="${SHADOW}">
<ellipse cx="${cx}" cy="62" rx="4.5" ry="1.4" fill="${INK}" opacity=".22"/>
<path d="M${cx} 46V61.6" stroke="#fff" stroke-width="3.5" stroke-linecap="round"/><path d="M${cx} 46V61.6" stroke="${STEM}" stroke-width="2" stroke-linecap="round"/>
<path d="M${cx} 57.5c3-.4 4.9-2.3 5.3-5-3 .3-4.9 2.1-5.3 5z" fill="${LEAF}"/>
<path d="M14 2H${w - 14}A12 12 0 0 1 ${w - 2} 14V30A12 12 0 0 1 ${w - 14} 42H${cx + 6}L${cx} 48L${cx - 6} 42H14A12 12 0 0 1 2 30V14A12 12 0 0 1 14 2Z" fill="#fff" stroke="${INK}" stroke-opacity=".16" stroke-linejoin="round"/>
${badges}${more}</svg>`;
    return { html, size: [w, 64], anchor: [cx, 62], popupOffset: -60, interactive: true };
}

function pt(radius: number, deg: number): [number, number] {
    const rad = (deg * Math.PI) / 180;
    return [38 + radius * Math.sin(rad), 38 - radius * Math.cos(rad)];
}

const LEAF_HUB = `<g transform="translate(31.4 31.4) scale(.55)"><path d="M4.5 19.5C4.5 10.5 10 5 20 4c-.6 10-6.2 15.5-15.5 15.5z" fill="${STEM}"/><path d="M4.5 19.5l9-9" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></g>`;

/** "Blüten-Glücksrad": a donut of measure segments with upright icons and a leaf hub. Beyond six, five plus "+n". */
export function flowerWheel(measures: MarkerMeasure[]): MarkerIcon {
    const list = measures.length === 0 ? [{ id: '', color: NEUTRAL, icon: null, name: '' }] : measures;
    const R = 32;
    const r = 13;
    let body: string;

    if (list.length === 1) {
        const m = list[0];
        const icon = m.icon ? `<image href="${m.icon}" x="29" y="29" width="18" height="18"/>` : '';
        body = `<path fill-rule="evenodd" fill="${m.color}" d="M38 6a32 32 0 1 0 0.001 0ZM38 25a13 13 0 1 1-0.001 0Z"/><circle cx="38" cy="38" r="12" fill="#fff"/>${icon}`;
    } else {
        const slots: Array<MarkerMeasure | { overflow: number }> = list.length > 6 ? [...list.slice(0, 5), { overflow: list.length - 5 }] : list;
        const k = slots.length;
        const sectors: string[] = [];
        const marks: string[] = [];
        slots.forEach((slot, i) => {
            const a0 = (i * 360) / k;
            const a1 = ((i + 1) * 360) / k;
            const large = a1 - a0 > 180 ? 1 : 0;
            const [x0, y0] = pt(R, a0);
            const [x1, y1] = pt(R, a1);
            const [ix1, iy1] = pt(r, a1);
            const [ix0, iy0] = pt(r, a0);
            const color = 'overflow' in slot ? '#e4e9e2' : slot.color;
            sectors.push(
                `<path fill="${color}" d="M${x0.toFixed(3)} ${y0.toFixed(3)}A${R} ${R} 0 ${large} 1 ${x1.toFixed(3)} ${y1.toFixed(3)}L${ix1.toFixed(3)} ${iy1.toFixed(3)}A${r} ${r} 0 ${large} 0 ${ix0.toFixed(3)} ${iy0.toFixed(3)}Z"/>`,
            );
            const [px, py] = pt(22.5, (a0 + a1) / 2);
            if ('overflow' in slot) {
                marks.push(
                    `<text x="${px.toFixed(2)}" y="${py.toFixed(2)}" font-family="system-ui,sans-serif" font-size="11" font-weight="700" fill="${INK}" text-anchor="middle" dominant-baseline="central">+${slot.overflow}</text>`,
                );
            } else if (slot.icon) {
                marks.push(
                    `<circle cx="${px.toFixed(2)}" cy="${py.toFixed(2)}" r="8.5" fill="#fff"/><image href="${slot.icon}" x="${(px - 6.5).toFixed(2)}" y="${(py - 6.5).toFixed(2)}" width="13" height="13"/>`,
                );
            }
        });
        body = `<g stroke="#fff" stroke-width="2" stroke-linejoin="round">${sectors.join('')}</g>${marks.join('')}${LEAF_HUB}`;
    }

    const html = `<svg xmlns="http://www.w3.org/2000/svg" width="76" height="92" viewBox="0 0 76 92" style="${SHADOW}">
<ellipse cx="38" cy="89.5" rx="5.5" ry="1.7" fill="${INK}" opacity=".22"/>
<path d="M38 72V89" stroke="#fff" stroke-width="4" stroke-linecap="round"/><path d="M38 72V89" stroke="${STEM}" stroke-width="2.4" stroke-linecap="round"/>
<path d="M38 84c3.6-.4 5.8-2.7 6.3-6-3.6.3-5.8 2.5-6.3 6z" fill="${LEAF}"/>
<circle cx="38" cy="38" r="35.5" fill="#fff" stroke="${INK}" stroke-opacity=".16"/>${body}</svg>`;
    return { html, size: [76, 92], anchor: [38, 89], popupOffset: -86, interactive: true };
}

function escapeXml(value: string): string {
    return value.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] ?? c);
}
