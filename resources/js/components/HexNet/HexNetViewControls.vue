<script setup lang="ts">
import { BASEMAPS, type Basemap, type MarkerStyle } from './hexnet';

defineProps<{
    measures: App.Data.MapPointCharacteristicData[];
}>();

const measureId = defineModel<string | null>('measure', { required: true });
const basemap = defineModel<Basemap>('basemap', { required: true });
const markerStyle = defineModel<MarkerStyle>('markerStyle', { required: true });
</script>

<template>
    <div class="hexnet-controls">
        <h2>Waben färben nach</h2>
        <div class="chips" role="group" aria-label="Waben färben nach">
            <button class="chip" :class="{ on: measureId === null }" :aria-pressed="measureId === null" @click="measureId = null">
                <span class="dot all" /> Alle Trittsteine
            </button>
            <button
                v-for="measure in measures"
                :key="measure.id"
                class="chip"
                :class="{ on: measureId === measure.id }"
                :aria-pressed="measureId === measure.id"
                :style="{ '--c': measure.color ?? '#7d8d85' }"
                @click="measureId = measureId === measure.id ? null : measure.id"
            >
                <span class="dot"><img v-if="measure.icon_path" :src="measure.icon_path" alt="" /></span>
                {{ measure.name }}
            </button>
        </div>

        <h2>Karte</h2>
        <select v-model="basemap" class="select" aria-label="Basiskarte">
            <option v-for="(option, key) in BASEMAPS" :key="key" :value="key">{{ option.label }}</option>
        </select>

        <h2>Marker in der Nahansicht</h2>
        <div class="toggle" role="group" aria-label="Marker in der Nahansicht">
            <button :class="{ on: markerStyle === 'card' }" :aria-pressed="markerStyle === 'card'" @click="markerStyle = 'card'">Card</button>
            <button :class="{ on: markerStyle === 'wheel' }" :aria-pressed="markerStyle === 'wheel'" @click="markerStyle = 'wheel'">Glücksrad</button>
        </div>
    </div>
</template>

<style scoped>
.hexnet-controls {
    padding: 15px 18px;
    border-bottom: 1px solid #dde5da;
}
h2 {
    margin: 14px 0 8px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: #7d8d85;
}
h2:first-child {
    margin-top: 0;
}
.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px 3px 3px;
    border: 1px solid #dde5da;
    border-radius: 999px;
    background: #fff;
    font-size: 11.5px;
    color: #16211c;
    cursor: pointer;
}
.chip:hover {
    border-color: var(--c, #1f7a34);
}
.chip.on {
    border-color: var(--c, #1f7a34);
    background: color-mix(in srgb, var(--c, #1f7a34) 14%, #fff);
    font-weight: 600;
}
.dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 999px;
    border: 2px solid var(--c);
    background: color-mix(in srgb, var(--c) 22%, #fff);
}
.dot img {
    width: 12px;
    height: 12px;
}
.dot.all {
    border: 0;
    background: conic-gradient(#b7e4a8 0 33%, #52b45a 0 66%, #1f7a34 0);
}
.select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #dde5da;
    border-radius: 6px;
    background: #fff;
    font-size: 12px;
}
.toggle {
    display: inline-flex;
    border: 1px solid #dde5da;
    border-radius: 6px;
    overflow: hidden;
}
.toggle button {
    padding: 6px 14px;
    font-size: 12px;
    background: #fff;
    border: 0;
    cursor: pointer;
}
.toggle button.on {
    background: #1f7a34;
    color: #fff;
    font-weight: 600;
}
</style>
