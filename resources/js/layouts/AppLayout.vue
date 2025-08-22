<script setup lang="ts">
import AppLayout from "@/layouts/app/AppSidebarLayout.vue";
import type { BreadcrumbItemType } from "@/layouts/helper";
import BrowserTitle from "./components/BrowserTitle.vue";

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <BrowserTitle />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
