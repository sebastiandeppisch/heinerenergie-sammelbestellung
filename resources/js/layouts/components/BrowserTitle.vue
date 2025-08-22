<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { PageProps } from "@inertiajs/core";
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";

interface CustomPageProps extends PageProps {
    auth: {
        currentGroup?: App.Data.GroupBaseData;
    };
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const header = computed(() => {
    return currentGroup.value?.name || "CRM";
});
</script>

<template>
    <Head :title="header" />
</template>
