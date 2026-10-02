<script setup lang="ts">
defineProps<{
    characteristic: Pick<App.Data.MapPointCharacteristicData, 'name' | 'color' | 'icon_path'>;
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :style="characteristic.color ? { borderColor: characteristic.color } : undefined"
        data-test="characteristic-badge"
    >
        <img v-if="characteristic.icon_path" :src="characteristic.icon_path" alt="" class="h-3.5 w-3.5 object-contain" />
        <span v-else-if="characteristic.color" class="h-2 w-2 rounded-full" :style="{ backgroundColor: characteristic.color }" />
        {{ characteristic.name }}
    </span>
</template>
