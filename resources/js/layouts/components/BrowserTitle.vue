<script setup lang="ts">
import { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CustomPageProps extends PageProps {
    auth: {
        currentGroup?: App.Data.GroupBaseData;
    };
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const header = computed(() => {
    return currentGroup.value?.name || 'CRM';
});
</script>

<template>
    <Head :title="header" />
</template>
