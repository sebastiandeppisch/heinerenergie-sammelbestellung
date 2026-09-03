<script setup lang="ts">
import AppContent from '@/layouts/components/AppContent.vue';
import AppShell from '@/layouts/components/AppShell.vue';
import AppSidebar from '@/layouts/components/AppSidebar.vue';
import AppSidebarHeader from '@/layouts/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/layouts/helper';
import { Toaster } from '@/shadcn/components/ui/sonner';
import type { CustomPageProps } from '@/types/pageProps';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
const page = usePage<CustomPageProps>();

watch(
    () => page.props.flashMessages,
    (newVal) => {
        for (const key in newVal) {
            if (newVal[key]) {
                const message = newVal[key];
                if (key === 'error') {
                    toast.error(message);
                } else if (key === 'success') {
                    toast.success(message);
                } else if (key === 'info') {
                    toast.info(message);
                } else if (key === 'warning') {
                    toast.warning(message);
                } else {
                    toast(message);
                }
            }
        }
    },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden bg-slate-50">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <Toaster class="pointer-events-auto" style="z-index: 9999" :richColors="true" position="top-center" />
            <slot />
        </AppContent>
    </AppShell>
</template>
