<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/layouts/helper';
import BrowserTitle from './components/BrowserTitle.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    fullBleed?: boolean;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    fullBleed: false,
});
</script>

<template>
    <BrowserTitle />
    <AppLayout :breadcrumbs="breadcrumbs" :full-bleed="fullBleed">
        <slot />
    </AppLayout>
</template>
