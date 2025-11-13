<script setup lang="ts">
import { PageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import genericLogo from '../../../img/logo.png';

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
    defaultLogo?: string;
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
const logo = computed(() => {
    if (currentGroup.value) {
        return currentGroup.value.logo_path || page.props.defaultLogo;
    }
    return genericLogo;
});
</script>

<template>
    <img :src="logo" />
</template>
