<script setup lang="ts">
import AppContent from '@/layouts/components/AppContent.vue';
import AppHeader from '@/layouts/components/AppHeader.vue';
import AppShell from '@/layouts/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/layouts/helper';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
