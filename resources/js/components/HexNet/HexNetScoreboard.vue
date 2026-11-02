<script setup lang="ts">
import './hexnet.css';
import { computed } from 'vue';
import { DEFAULT_LEVEL_COLORS, LEVELS, MEASURE_OPACITY } from './hexnet';

const props = defineProps<{
    stats: App.HexNet.Data.HexNetStatsData;
    thresholds: { medium: number; dark: number };
    /** A measure color replaces the greens, with the same steps of strength as on the map. */
    color?: string | null;
    levelColors?: string[];
}>();

const levelColors = computed(() => props.levelColors ?? DEFAULT_LEVEL_COLORS);
const fill = (level: number) =>
    props.color
        ? level === 0
            ? levelColors.value[0]
            : `color-mix(in srgb, ${props.color} ${MEASURE_OPACITY[level] * 100}%, #fff)`
        : levelColors.value[level];

const width = (level: number) => (props.stats.playable_cells ? (100 * props.stats.levels[level]) / props.stats.playable_cells : 0) + '%';

const steps = computed(() => [
    { level: 3, hint: `ab ${props.thresholds.dark} Trittsteinen` },
    { level: 2, hint: `ab ${props.thresholds.medium} Trittsteinen` },
    { level: 1, hint: '1 Trittstein' },
    { level: 0, hint: 'hier fehlt der erste' },
]);
</script>

<template>
    <div>
        <div class="hexnet-progress">
            <div class="big">{{ stats.percent }}<span> %</span></div>
            <div class="cap">der bespielbaren Fläche hat mindestens einen Trittstein</div>
            <div class="track">
                <i :style="{ width: width(3), background: fill(3) }" />
                <i :style="{ width: width(2), background: fill(2) }" />
                <i :style="{ width: width(1), background: fill(1) }" />
            </div>
        </div>
        <div class="hexnet-group">
            <h2>Spielstand</h2>
            <div v-for="step in steps" :key="step.level" class="step">
                <span class="hexnet-swatch" :style="{ background: fill(step.level) }" />
                <div class="lbl">
                    <b>{{ LEVELS[step.level].name }}</b
                    ><small>{{ step.hint }}</small>
                </div>
                <div class="cnt">{{ stats.levels[step.level] }}</div>
            </div>
            <div class="total">
                <span>Trittsteine insgesamt</span><b>{{ stats.total_points }}</b>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hexnet-progress {
    padding: 18px;
    background: #f1f5f0;
    border-bottom: 1px solid #dde5da;
}
.big {
    font-size: 40px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.03em;
    color: #1f7a34;
    font-variant-numeric: tabular-nums;
}
.big span {
    font-size: 20px;
    font-weight: 600;
}
.cap {
    font-size: 12px;
    color: #46564e;
    margin-top: 6px;
    line-height: 1.45;
}
.track {
    height: 12px;
    border-radius: 6px;
    background: #dfe6dc;
    overflow: hidden;
    display: flex;
    margin-top: 12px;
}
.track i {
    display: block;
    height: 100%;
    transition: width 0.6s ease;
}
.hexnet-group {
    padding: 15px 18px;
    border-bottom: 1px solid #dde5da;
}
h2 {
    margin: 0 0 11px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #7d8d85;
}
.step {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.lbl {
    flex: 1;
    font-size: 12px;
}
.lbl b {
    display: block;
    font-weight: 600;
}
.lbl small {
    color: #7d8d85;
    font-size: 10.5px;
}
.cnt {
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 600;
}
.total {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px dashed #dde5da;
    font-size: 12px;
}
.total b {
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
}
</style>
