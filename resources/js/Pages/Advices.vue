<script setup lang="ts">
import AdvicesTable from '@/components/Advices/AdvicesTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { setLayoutProps } from '@inertiajs/vue3';

defineProps<{
    showGroupColumn: boolean;
    advices: App.Data.DataProtectedAdviceData[];
    groups: App.Data.GroupData[];
    adviceStatuses: { id: string; name: string }[];
    adviceTypes: { id: number; name: string }[];
    advisors: { id: string; name: string }[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Beratungen' }, { title: 'Tabelle' }],
});
</script>

<template>
    <PageHeader title="Beratungen" />
    <AdvicesTable
        :show-group-column="showGroupColumn"
        :advices="advices"
        :groups="groups"
        :advice-statuses="adviceStatuses"
        :advice-types="adviceTypes"
        :advisors="advisors"
    />
</template>
