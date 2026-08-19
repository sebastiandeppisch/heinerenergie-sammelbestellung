<script setup lang="ts">
import AdvicesTable from '@/components/Advices/AdvicesTable.vue';
import LegacyAdvicesTable from '@/components/Advices/LegacyAdvicesTable.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { ref } from 'vue';

defineProps<{
    showGroupColumn: boolean;
    advices: App.Data.DataProtectedAdviceData[];
    groups: App.Data.GroupData[];
    adviceStatuses: { id: string; name: string }[];
    adviceTypes: { id: number; name: string }[];
    advisors: { id: string; name: string }[];
}>();

const activeTab = ref('new');
</script>

<template>
    <Tabs v-model="activeTab" default-value="new" class="w-full">
        <TabsList>
            <TabsTrigger value="new">Neue Ansicht (beta)</TabsTrigger>
            <TabsTrigger value="legacy">Alte Ansicht</TabsTrigger>
        </TabsList>

        <TabsContent value="new">
            <AdvicesTable
                :show-group-column="showGroupColumn"
                :advices="advices"
                :groups="groups"
                :advice-statuses="adviceStatuses"
                :advice-types="adviceTypes"
                :advisors="advisors"
            />
        </TabsContent>

        <TabsContent value="legacy">
            <LegacyAdvicesTable :show-group-column="showGroupColumn" :advices="advices" :groups="groups" />
        </TabsContent>
    </Tabs>
</template>
