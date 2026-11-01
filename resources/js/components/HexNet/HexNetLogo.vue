<script setup lang="ts">
withDefaults(defineProps<{ wordmark?: boolean }>(), { wordmark: true });

const R = 30;
const H = R * 0.866;
const GAP = 0.9;

const cells: Array<[number, number, string]> = [
    [0, 1, '#e3e6e1'],
    [1, 0, '#e3e6e1'],
    [1, 1, '#8fbf5a'],
    [2, 0, '#5fa043'],
    [2, 1, '#1f6e34'],
    [3, 0, '#1f6e34'],
    [3, 1, '#c4dca6'],
    [4, 0, '#e3e6e1'],
    [4, 1, '#e3e6e1'],
    [5, 1, '#e3e6e1'],
];

function center(col: number, row: number): [number, number] {
    return [40 + col * 1.5 * R, 92 + row * 2 * H + (col % 2 === 0 ? 0 : H)];
}

function hexPoints(col: number, row: number): string {
    const [cx, cy] = center(col, row);
    return Array.from({ length: 6 }, (_, i) => {
        const a = (Math.PI / 3) * i;
        return `${(cx + R * GAP * Math.cos(a)).toFixed(1)},${(cy + R * GAP * Math.sin(a)).toFixed(1)}`;
    }).join(' ');
}

const nodes = [center(1, 1), center(2, 0), center(3, 0)];
</script>

<template>
    <svg :viewBox="wordmark ? '0 0 300 250' : '0 0 300 200'" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="HexaNetz">
        <g fill="#c9ccc6" transform="translate(0 12)">
            <rect x="34" y="30" width="22" height="24" />
            <rect x="62" y="22" width="26" height="32" />
            <polygon points="96,40 108,28 120,40 120,56 96,56" fill="#f4f5f3" stroke="#9aa39b" stroke-width="1" />
            <rect x="128" y="12" width="16" height="42" />
            <rect x="150" y="30" width="14" height="24" />
            <polygon points="178,54 178,26 183,8 188,26 188,54" />
            <rect x="200" y="32" width="28" height="22" fill="#f4f5f3" stroke="#9aa39b" stroke-width="1" />
        </g>
        <g fill="#7cb342" transform="translate(0 12)">
            <circle cx="28" cy="46" r="8" />
            <circle cx="92" cy="44" r="7" />
            <circle cx="166" cy="46" r="8" />
            <circle cx="238" cy="42" r="9" />
            <circle cx="256" cy="50" r="6" />
        </g>
        <polygon v-for="([col, row, color], index) in cells" :key="index" :points="hexPoints(col, row)" :fill="color" stroke-linejoin="round" />
        <polyline :points="nodes.map(([x, y]) => `${x},${y}`).join(' ')" fill="none" stroke="#ffffff" stroke-width="3" />
        <g v-for="([x, y], index) in nodes" :key="`n${index}`">
            <circle :cx="x" :cy="y" r="7" fill="#ffffff" />
            <circle :cx="x" :cy="y" r="4.5" fill="#16211c" />
        </g>
        <text
            v-if="wordmark"
            x="150"
            y="242"
            text-anchor="middle"
            font-family="Inter, 'Segoe UI', Roboto, sans-serif"
            font-size="44"
            font-weight="800"
            letter-spacing="-1"
        >
            <tspan fill="#16211c">Hexa</tspan>
            <tspan fill="#6a9a3a">Netz</tspan>
        </text>
    </svg>
</template>
