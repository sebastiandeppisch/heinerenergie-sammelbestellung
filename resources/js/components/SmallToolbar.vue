<script setup lang="ts">
import AppLogo from '@/layouts/components/AppLogo.vue';
import type { CustomPageProps } from '@/types/pageProps';
import { router, usePage } from '@inertiajs/vue3';
import { User } from 'lucide-vue-next';
import { computed } from 'vue';

const openBackend = () => {
    router.visit('/backend');
};

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const header = computed(() => {
    return currentGroup.value?.name || 'CRM';
});
</script>

<template>
    <header class="flex items-center justify-between rounded-b-lg border-b bg-white px-4 py-2 shadow-sm">
        <!-- Left spacer -->
        <div class="w-[70px]"></div>

        <!-- Logo -->
        <div class="flex items-center">
            <AppLogo class="h-12 w-24 object-contain" />
        </div>

        <!-- Center title -->
        <div class="flex-1 text-center">
            <h1 class="text-2xl font-semibold text-gray-800">{{ header }}</h1>
        </div>

        <!-- Right button -->
        <div class="flex items-center">
            <button
                @click="openBackend"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
            >
                <User class="h-4 w-4" />
                <span class="hidden sm:inline">Berater:innen Zugang</span>
            </button>
        </div>
    </header>
</template>
