<script setup lang="ts">
import { ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import AdvicesTable from '@/components/Advices/AdvicesTable.vue';
import LegacyAdvicesTable from '@/components/Advices/LegacyAdvicesTable.vue';

defineProps<{
    onlyOneGroup: boolean;
    advices: App.Models.Advice[];
    groups: App.Data.GroupData[];
    adviceStatuses: { id: number; name: string }[];
    adviceTypes: { id: number; name: string }[];
    advisors: { id: number; name: string }[];
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
                :only-one-group="onlyOneGroup"
                :advices="advices"
                :groups="groups"
                :advice-statuses="adviceStatuses"
                :advice-types="adviceTypes"
                :advisors="advisors"
            />
        </TabsContent>

        <TabsContent value="legacy">
            <LegacyAdvicesTable
                :only-one-group="onlyOneGroup"
                :advices="advices"
                :groups="groups"
            />
        </TabsContent>
    </Tabs>
</template>
