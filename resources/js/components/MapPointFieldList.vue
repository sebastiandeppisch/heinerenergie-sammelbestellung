<script setup lang="ts">
import ImageLightbox from '@/components/ImageLightbox.vue';
import MapPointCharacteristicBadge from '@/components/MapPointCharacteristics/MapPointCharacteristicBadge.vue';
import { Lock } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        fields: Array<App.Data.MapPointFieldValueData>;
        /** The characteristics of the point. Each is shown as a badge with its fields below, also without fields. */
        characteristics?: Array<App.Data.MapPointCharacteristicData>;
        /** Marks internal fields, for admins who see public and internal values side by side. */
        markInternal?: boolean;
    }>(),
    {
        characteristics: () => [],
        markInternal: false,
    },
);

/** Fields of the category first, then each characteristic with its fields. */
const categoryFields = computed(() => props.fields.filter((field) => field.characteristic_id === null));
const characteristicGroups = computed(() =>
    props.characteristics.map((characteristic) => ({
        characteristic,
        fields: props.fields.filter((field) => field.characteristic_id === characteristic.id),
    })),
);
const groups = computed(() => [
    ...(categoryFields.value.length > 0 ? [{ id: 'category', characteristic: null, fields: categoryFields.value }] : []),
    ...characteristicGroups.value.map((group) => ({ id: group.characteristic.id, ...group })),
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
    <!-- Each label stands above its value, so long labels and values never squeeze each other in narrow popups. -->
    <div class="space-y-3 text-sm" data-test="map-point-fields">
        <div v-for="group in groups" :key="group.id" class="space-y-1.5">
            <div v-if="group.characteristic" data-test="map-point-fields-characteristic">
                <MapPointCharacteristicBadge :characteristic="group.characteristic" />
            </div>
            <!-- Below a characteristic, a line in its color ties the fields to the badge. -->
            <dl
                v-if="group.fields.length > 0"
                class="space-y-2"
                :class="{ 'ml-3 border-l-2 pl-3': group.characteristic }"
                :style="group.characteristic?.color ? { borderColor: group.characteristic.color } : undefined"
            >
                <div v-for="field in group.fields" :key="field.id">
                    <dt class="flex items-start gap-1 text-xs text-muted-foreground">
                        {{ field.label }}
                        <Lock v-if="markInternal && !field.is_public" class="mt-0.5 h-3 w-3 shrink-0" aria-label="intern" />
                    </dt>
                    <!-- The minimum width keeps Leaflet popups, which measure their width before the images are loaded, wide enough. -->
                    <dd v-if="field.type === 'image'" class="mt-1 flex min-w-48 flex-wrap gap-2" data-test="map-point-field-images">
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
                </div>
            </dl>
        </div>
    </div>

    <ImageLightbox v-model:src="openedImageUrl" />
</template>
