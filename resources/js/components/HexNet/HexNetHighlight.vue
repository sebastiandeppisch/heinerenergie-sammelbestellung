<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { onMounted, ref } from 'vue';
import HexNetMap from './HexNetMap.vue';
import type { HexCells } from './hexnet';

const props = defineProps<{
    highlight: App.HexNet.Data.HexNetHighlightData;
}>();

const mapRef = ref<InstanceType<typeof HexNetMap> | null>(null);

onMounted(() => setTimeout(() => mapRef.value?.flyTo(props.highlight.coordinate, 15.5), 600));
</script>

<template>
    <div class="w-full space-y-4 text-left" data-test="hexnet-highlight">
        <div class="h-[360px] overflow-hidden rounded-lg border">
            <HexNetMap
                ref="mapRef"
                :cells="highlight.cells as HexCells"
                :counts="highlight.counts"
                :thresholds="{ medium: highlight.hex_net.threshold_medium, dark: highlight.hex_net.threshold_dark }"
                :bounds="highlight.hex_net.bounds"
                :highlight="{ cell: highlight.cell, coordinate: highlight.coordinate }"
                :interactive="false"
                :level-colors="highlight.hex_net.level_colors"
            />
        </div>
        <p class="text-base font-semibold">
            <template v-if="(highlight.counts[highlight.cell] ?? 0) > 0">Deine Wabe zählt jetzt.</template>
            <template v-else>Dein Garten ist dabei. Mit Maßnahmen färbt er deine Wabe grün.</template>
            <template v-if="highlight.published">Dein Garten ist schon auf der Karte zu sehen.</template>
            <template v-else>Dein Garten erscheint nach Freigabe auf der Karte.</template>
        </p>
        <div class="flex flex-wrap gap-2">
            <Button v-if="highlight.follow_up_url" as="a" :href="highlight.follow_up_url" data-test="hexnet-follow-up">{{
                highlight.follow_up_label
            }}</Button>
            <Button as="a" variant="outline" :href="highlight.public_url">Zum Spielstand von „{{ highlight.hex_net.title }}“</Button>
        </div>
    </div>
</template>
