<script setup lang="ts">
import { Lock } from '@lucide/vue';

withDefaults(
    defineProps<{
        fields: Array<App.Data.MapPointFieldValueData>;
        /** Marks internal fields, for admins who see public and internal values side by side. */
        markInternal?: boolean;
    }>(),
    {
        markInternal: false,
    },
);

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
        <template v-for="field in fields" :key="field.id">
            <dt class="flex items-start gap-1 text-muted-foreground">
                {{ field.label }}
                <Lock v-if="markInternal && !field.is_public" class="mt-0.5 h-3 w-3 shrink-0" aria-label="intern" />
            </dt>
            <dd class="break-words whitespace-pre-line">
                <a v-if="href(field)" :href="href(field)!" class="underline">{{ field.display_value }}</a>
                <template v-else>{{ field.display_value }}</template>
            </dd>
        </template>
    </dl>
</template>
