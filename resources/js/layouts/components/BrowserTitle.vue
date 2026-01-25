<script setup lang="ts">
import type { CustomPageProps } from '@/types/pageProps';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const header = computed(() => {
    return currentGroup.value?.name || 'CRM';
});
</script>

<template>
    <Head :title="header" />
</template>
