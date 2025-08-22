<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { User } from "lucide-vue-next";
import AppLogo from "@/layouts/components/AppLogo.vue";
import { PageProps } from "@inertiajs/core";
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
const openBackend = () => {
    router.visit("/backend");
};

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const header = computed(() => {
    return currentGroup.value?.name || "CRM";
});
</script>

<template>
    <header
        class="flex items-center justify-between px-4 py-2 bg-white border-b shadow-sm rounded-b-lg"
    >
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
                class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
            >
                <User class="h-4 w-4" />
                <span class="hidden sm:inline">Berater:innen Zugang</span>
            </button>
        </div>
    </header>
</template>
