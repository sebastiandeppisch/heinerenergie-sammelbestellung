<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Tooltip from '@/shadcn/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/shadcn/components/ui/tooltip/TooltipContent.vue';
import TooltipTrigger from '@/shadcn/components/ui/tooltip/TooltipTrigger.vue';

type Address = App.ValueObjects.Address;

const props = defineProps<{ value: Address }>();

const formatted = computed(() => `${props.value.street} ${props.value.street_number}, ${props.value.zip} ${props.value.city}`);
const osmUrl = computed(() => `https://www.openstreetmap.org/search?query=${encodeURIComponent(formatted.value)}`);

const geocoded = ref<{ lat: number; lng: number } | null>(null);
const geocodingDone = ref(false);
const geocodingFailed = ref(false);

const osmEmbedUrl = computed(() => {
    if (!geocoded.value) return null;
    const bbox = 0.005;
    const { lat, lng } = geocoded.value;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - bbox},${lat - bbox},${lng + bbox},${lat + bbox}&layer=mapnik&marker=${lat},${lng}`;
});

async function geocode() {
    if (geocodingDone.value) return;
    geocodingDone.value = true;
    try {
        const query = encodeURIComponent(formatted.value);
        const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json&limit=1&countrycodes=de`, {
            headers: { 'Accept-Language': 'de' },
        });
        const results = await response.json();
        if (results.length > 0) {
            geocoded.value = { lat: parseFloat(results[0].lat), lng: parseFloat(results[0].lon) };
        } else {
            geocodingFailed.value = true;
        }
    } catch {
        geocodingFailed.value = true;
    }
}
</script>

<template>
    <Tooltip :delay-duration="300">
        <TooltipTrigger as-child>
            <a :href="osmUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-0.5 hover:underline" @mouseenter="geocode" @click.stop>
                <MapPin class="h-3 w-3 shrink-0 text-gray-400" />
                {{ formatted }}
            </a>
        </TooltipTrigger>
        <TooltipContent v-if="osmEmbedUrl" class="overflow-hidden p-0" :side-offset="8">
            <iframe :src="osmEmbedUrl" width="300" height="200" class="block border-none" style="pointer-events: none" loading="lazy" />
        </TooltipContent>
        <TooltipContent v-else-if="geocodingFailed" :side-offset="8">
            Adresse nicht gefunden
        </TooltipContent>
    </Tooltip>
</template>
