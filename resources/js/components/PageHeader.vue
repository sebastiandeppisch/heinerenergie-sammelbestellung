<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl leading-9 font-semibold tracking-tight text-foreground">{{ title }}</h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center justify-end gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
