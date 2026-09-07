<script setup lang="ts">
import Tooltip from '@/shadcn/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/shadcn/components/ui/tooltip/TooltipContent.vue';
import TooltipTrigger from '@/shadcn/components/ui/tooltip/TooltipTrigger.vue';
import axios from 'axios';
import { MapPin } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

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

/**
 * Goes through our own backend rather than calling Nominatim from the browser.
 * That keeps the shared cache and the rate limit towards OpenStreetMap in one
 * place, instead of every viewer querying it with their own IP address.
 */
async function geocode() {
    if (geocodingDone.value) return;
    geocodingDone.value = true;
    try {
        const { data } = await axios.post<{ coordinate: App.ValueObjects.Coordinate | null }>(route('api.geocode.address'), {
            street: props.value.street,
            street_number: props.value.street_number,
            zip: props.value.zip,
            city: props.value.city,
        });

        if (data.coordinate) {
            geocoded.value = { lat: data.coordinate.lat, lng: data.coordinate.lng };
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
            <a
                :href="osmUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-0.5 hover:underline"
                @mouseenter="geocode"
                @click.stop
            >
                <MapPin class="h-3 w-3 shrink-0 text-gray-400" />
                {{ formatted }}
            </a>
        </TooltipTrigger>
        <TooltipContent v-if="osmEmbedUrl" class="overflow-hidden p-0" :side-offset="8">
            <iframe :src="osmEmbedUrl" width="300" height="200" class="block border-none" style="pointer-events: none" loading="lazy" />
        </TooltipContent>
        <TooltipContent v-else-if="geocodingFailed" :side-offset="8"> Adresse nicht gefunden </TooltipContent>
    </Tooltip>
</template>
