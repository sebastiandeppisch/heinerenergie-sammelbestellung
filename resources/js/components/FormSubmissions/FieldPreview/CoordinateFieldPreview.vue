<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';
import { computed } from 'vue';
import Tooltip from '@/shadcn/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/shadcn/components/ui/tooltip/TooltipContent.vue';
import TooltipTrigger from '@/shadcn/components/ui/tooltip/TooltipTrigger.vue';

type Coordinate = App.ValueObjects.Coordinate;

const props = defineProps<{ value: Coordinate }>();

const label = computed(() => `${props.value.lat.toFixed(5)}, ${props.value.lng.toFixed(5)}`);
const osmUrl = computed(
    () => `https://www.openstreetmap.org/?mlat=${props.value.lat}&mlon=${props.value.lng}#map=15/${props.value.lat}/${props.value.lng}`,
);
const osmEmbedUrl = computed(() => {
    const bbox = 0.005;
    const { lat, lng } = props.value;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - bbox},${lat - bbox},${lng + bbox},${lat + bbox}&layer=mapnik&marker=${lat},${lng}`;
});
</script>

<template>
    <Tooltip :delay-duration="300">
        <TooltipTrigger as-child>
            <a :href="osmUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-0.5 font-mono text-xs hover:underline" @click.stop>
                <MapPin class="h-3 w-3 shrink-0 text-gray-400" />
                {{ label }}
            </a>
        </TooltipTrigger>
        <TooltipContent class="overflow-hidden p-0" :side-offset="8">
            <iframe :src="osmEmbedUrl" width="300" height="200" class="block border-none" style="pointer-events: none" loading="lazy" />
        </TooltipContent>
    </Tooltip>
</template>
