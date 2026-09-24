<script setup lang="ts">
import PublicLayout from '../layouts/PublicLayout.vue';

const props = defineProps<{ content: string }>();

defineOptions({
    layout: PublicLayout,
});
</script>
<template>
    <div class="mt-8">
        <div class="content rounded-xl border bg-card p-4 text-card-foreground shadow-sm">
            <div v-html="props.content"></div>
        </div>
    </div>
</template>
