<script setup lang="ts">
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import { Button } from '@/shadcn/components/ui/button';
import { MapPin, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    entry: App.HexNet.Data.HexNetPointData;
    contactUrl?: string | null;
}>();

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
