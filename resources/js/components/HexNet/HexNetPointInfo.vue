<script setup lang="ts">
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import { Button } from '@/shadcn/components/ui/button';
import { MapPin, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    entry: App.HexNet.Data.HexNetPointData;
    contactUrl?: string | null;
    /** All measures of the campaign, the ones of this stepping stone in color, the others greyed out. */
    measures?: App.Data.MapPointCharacteristicData[];
}>();

const doneIds = computed(() => new Set(props.entry.point.characteristics.map((c) => c.id)));

const point = computed(() => props.entry.point);
const images = computed(() => point.value.fields.flatMap((field) => field.images));
const fieldsWithoutImages = computed(() => point.value.fields.filter((field) => field.images.length === 0));
</script>

<template>
    <div class="space-y-2 text-[13px] text-[#16211c]">
        <div>
            <h3 class="m-0 text-sm font-bold">{{ point.title }}</h3>
            <div class="text-[11.5px] text-[#7d8d85]">Trittstein seit {{ entry.since }}</div>
        </div>

        <ul v-if="measures?.length" class="m-0 flex list-none flex-wrap gap-1.5 p-0" aria-label="Maßnahmen">
            <li
                v-for="measure in measures"
                :key="measure.id"
                class="hexnet-measure"
                :class="{ missing: !doneIds.has(measure.id) }"
                :style="{ '--c': measure.color ?? '#7d8d85' }"
                :title="`${measure.name}${doneIds.has(measure.id) ? '' : ' (noch nicht)'}`"
            >
                <img v-if="measure.icon_path" :src="measure.icon_path" alt="" />
                <span class="sr-only">{{ measure.name }}{{ doneIds.has(measure.id) ? '' : ': noch nicht' }}</span>
            </li>
        </ul>

        <div v-if="images.length > 0" class="-mx-1 flex snap-x snap-mandatory gap-2 overflow-x-auto px-1 pb-1">
            <img
                v-for="image in images"
                :key="image.url"
                :src="image.url"
                :alt="image.name"
                class="h-32 w-auto max-w-[85%] shrink-0 snap-center rounded-md object-cover"
            />
        </div>

        <p v-if="point.description" class="m-0 whitespace-pre-line">{{ point.description }}</p>

        <MapPointFieldList :fields="fieldsWithoutImages" :characteristics="point.characteristics" />

        <Button v-if="point.has_contact && contactUrl" as="a" :href="contactUrl" variant="outline" size="sm" class="w-full">
            <MessageCircle class="mr-1 h-4 w-4" />
            Kontakt aufnehmen
        </Button>

        <div class="flex items-center gap-1 text-[11px] text-[#7d8d85]">
            <MapPin class="h-3 w-3" />
            Standort leicht verschoben
        </div>
    </div>
</template>

<style scoped>
.hexnet-measure {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 999px;
    border: 2px solid var(--c);
    background: color-mix(in srgb, var(--c) 22%, #fff);
}
.hexnet-measure img {
    width: 14px;
    height: 14px;
}
.hexnet-measure.missing {
    border-color: #dde5da;
    background: #f4f6f3;
}
.hexnet-measure.missing img {
    opacity: 0.3;
}
</style>
