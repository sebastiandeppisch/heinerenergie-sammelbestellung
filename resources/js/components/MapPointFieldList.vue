<script setup lang="ts">
import ImageLightbox from '@/components/ImageLightbox.vue';
import { Lock } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        fields: Array<App.Data.MapPointFieldValueData>;
        /** The characteristics of the point, to group their fields under their name. */
        characteristics?: Array<App.Data.MapPointCharacteristicData>;
        /** Marks internal fields, for admins who see public and internal values side by side. */
        markInternal?: boolean;
    }>(),
    {
        characteristics: () => [],
        markInternal: false,
    },
);

/** Fields of the category first, then the fields of each characteristic under its name. */
const groups = computed(() => [
    { id: null, name: null, fields: props.fields.filter((field) => field.characteristic_id === null) },
    ...props.characteristics.map((characteristic) => ({
        id: characteristic.id,
        name: characteristic.name,
        fields: props.fields.filter((field) => field.characteristic_id === characteristic.id),
    })),
]);

const openedImageUrl = ref<string | null>(null);

function href(field: App.Data.MapPointFieldValueData): string | null {
    if (field.type === 'email') {
        return `mailto:${field.value}`;
    }
    if (field.type === 'phone') {
        return `tel:${String(field.value).replace(/[^\d+]/g, '')}`;
    }
    return null;
}
</script>

<template>
    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm" data-test="map-point-fields">
        <template v-for="group in groups" :key="group.id ?? 'category'">
            <template v-if="group.fields.length > 0">
                <dt v-if="group.name" class="col-span-2 mt-1 text-xs font-semibold" data-test="map-point-fields-characteristic">
                    {{ group.name }}
                </dt>
                <template v-for="field in group.fields" :key="field.id">
                    <dt class="flex items-start gap-1 text-muted-foreground">
                        {{ field.label }}
                        <Lock v-if="markInternal && !field.is_public" class="mt-0.5 h-3 w-3 shrink-0" aria-label="intern" />
                    </dt>
                    <!-- Images get a row of their own below the label. The minimum width keeps Leaflet popups, which measure their width before the images are loaded, wide enough. -->
                    <dd v-if="field.type === 'image'" class="col-span-2 flex min-w-48 flex-wrap gap-2" data-test="map-point-field-images">
                        <button
                            v-for="image in field.images"
                            :key="image.name"
                            type="button"
                            class="cursor-zoom-in"
                            @click="openedImageUrl = image.url"
                        >
                            <img
                                :src="`${image.url}?h=128`"
                                :alt="field.label"
                                loading="lazy"
                                class="h-16 w-auto max-w-full rounded border object-contain"
                            />
                        </button>
                    </dd>
                    <dd v-else class="break-words whitespace-pre-line">
                        <a v-if="href(field)" :href="href(field)!" class="underline">{{ field.display_value }}</a>
                        <template v-else>{{ field.display_value }}</template>
                    </dd>
                </template>
            </template>
        </template>
    </dl>

    <ImageLightbox v-model:src="openedImageUrl" />
</template>
