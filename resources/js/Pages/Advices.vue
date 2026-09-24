<script setup lang="ts">
import AdvicesTable from '@/components/Advices/AdvicesTable.vue';

defineProps<{
    showGroupColumn: boolean;
    advices: App.Data.DataProtectedAdviceData[];
    groups: App.Data.GroupData[];
    adviceStatuses: { id: string; name: string }[];
    adviceTypes: { id: number; name: string }[];
    advisors: { id: string; name: string }[];
}>();
</script>

<template>
    <AdvicesTable
        :show-group-column="showGroupColumn"
        :advices="advices"
        :groups="groups"
        :advice-statuses="adviceStatuses"
        :advice-types="adviceTypes"
        :advisors="advisors"
    />
</template>
