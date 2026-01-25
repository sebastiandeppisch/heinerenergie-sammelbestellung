<script setup lang="ts">
import type { CustomPageProps } from '@/types/pageProps';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import genericLogo from '../../../img/logo.png';

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const logo = computed(() => {
    if (currentGroup.value && currentGroup.value.logo_path) {
        return currentGroup.value.logo_path;
    }
    if (page.props.defaultLogo) {
        return page.props.defaultLogo;
    }
    return genericLogo;
});
</script>

<template>
    <img :src="logo" />
</template>
