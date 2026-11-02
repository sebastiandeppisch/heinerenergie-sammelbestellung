<script setup lang="ts">
import { computed } from 'vue';
import type { CellSelection } from './HexNetMap.vue';
import { DEFAULT_LEVEL_COLORS, LEVELS, missingToNextLevel, SIGNAL, steppingStones, type Thresholds } from './hexnet';

const props = defineProps<{
    cell: CellSelection;
    thresholds: Thresholds;
    formUrl?: string | null;
    measureName?: string | null;
    measureColor?: string | null;
    levelColors?: string[];
}>();

const missing = computed(() => missingToNextLevel(props.cell.count, props.thresholds));
const joinUrl = computed(() => {
    if (!props.formUrl) return null;
    const url = new URL(props.formUrl, window.location.origin);
    url.searchParams.set('lat', props.cell.center.lat.toFixed(6));
    url.searchParams.set('lng', props.cell.center.lng.toFixed(6));
    return url.toString();
});
</script>

<template>
    <div class="space-y-1 text-[13px] text-[#16211c]">
        <div class="flex items-center gap-2">
            <span
                class="hexnet-swatch"
                :style="{ background: measureColor && cell.level > 0 ? measureColor : (levelColors ?? DEFAULT_LEVEL_COLORS)[cell.level] }"
            />
            <h3 class="m-0 text-sm font-bold">{{ LEVELS[cell.level].name }}</h3>
        </div>
        <div>
            {{ cell.count }} {{ steppingStones(cell.count) }} <template v-if="measureName">mit „{{ measureName }}“ </template>in dieser Wabe
        </div>
        <div class="font-semibold" :style="{ color: SIGNAL }">
            <template v-if="missing">Noch {{ missing.missing }} {{ steppingStones(missing.missing) }} bis {{ missing.next }}.</template>
            <template v-else>Höchste Stufe erreicht.</template>
        </div>
        <a
            v-if="joinUrl"
            :href="joinUrl"
            class="mt-2 inline-flex w-full items-center justify-center rounded-md px-3 py-2 text-[12.5px] font-semibold !text-white no-underline"
            :style="{ background: SIGNAL }"
        >
            Hier mitmachen
        </a>
    </div>
</template>
