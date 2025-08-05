<script setup lang="ts">
import AppContent from '@/layouts/components/AppContent.vue';
import AppShell from '@/layouts/components/AppShell.vue';
import AppSidebar from '@/layouts/components/AppSidebar.vue';
import AppSidebarHeader from '@/layouts/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/layouts/helper';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden bg-slate-50">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
