<template>
    <div class="py-12">
        <div class="mx-auto space-y-6 sm:px-6 lg:px-8">
            <!-- Title section -->
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Initiativen-Struktur</h2>
                <div class="flex items-center gap-4">
                    <Button
                        v-if="canCreateGroups"
                        variant="default"
                        @click="showCreateModal = true"
                    >
                        <Plus class="h-4 w-4" />
                        Neue Initiative anlegen
                    </Button>
                </div>
            </div>

            <!-- Content section -->
            <div class="flex gap-6">
                <!-- Tree view card -->
                <div class="w-180 shrink-0 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <GroupTree :groups="props.groupTreeItems" :selected-group="selectedGroup" />
                    </div>
                </div>

                <!-- Details card -->
                <div v-if="selectedGroup" class="flex-grow overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <DxTabPanel :selected-index="selectedTabIndex" @selection-changed="onTabSelectionChanged">
                            <DxItem title="Stammdaten" icon="info">
                                <template #default>
                                    <GroupDetails :group="selectedGroup" :can-edit="canEditGroup" />
                                </template>
                            </DxItem>
                            <DxItem title="Berater:innen" icon="user" v-if="canEditGroup">
                                <template #default>
                                    <GroupUsers :group="selectedGroup" />
                                </template>
                            </DxItem>
                            <DxItem title="Beratungsgebiet" icon="map">
                                <template #default>
                                    <ConsultingAreaForm :group="selectedGroup" :polygon="polygon" />
                                </template>
                            </DxItem>
                            <DxItem title="Beratungszustände" icon="tableproperties" v-if="canEditGroup">
                                <template #default>
                                    <AdviceStatusGroup :group="selectedGroup" :groups="groups" />
                                </template>
                            </DxItem>
                        </DxTabPanel>
                    </div>
                </div>
                <div v-else class="flex-grow overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">Bitte wähle eine Initiative aus, um die Details anzuzeigen</div>
                </div>
            </div>
        </div>
    </div>
    <DxPopup
        v-model:visible="showCreateModal"
        :title="'Neue Initiative anlegen'"
        :show-title="true"
        :width="600"
        :height="'auto'"
        :show-close-button="true"
    >
        <CreateGroupForm :parent-groups="groups" :parent-required="!canCreateRootGroup" @close="showCreateModal = false" />
    </DxPopup>
</template>

<script setup lang="ts">
import ConsultingAreaForm from '@/components/ConsultingArea/ConsultingAreaForm.vue';
import CreateGroupForm from '@/components/Groups/CreateGroupForm.vue';
import GroupDetails from '@/components/Groups/GroupDetails.vue';
import GroupTree from '@/components/Groups/GroupTree.vue';
import GroupUsers from '@/components/Groups/GroupUsers.vue';
import AdviceStatusGroup from '@/views/AdviceStatusGroup.vue';
import { DxPopup } from 'devextreme-vue/popup';
import { DxItem, DxTabPanel } from 'devextreme-vue/tab-panel';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { Plus } from 'lucide-vue-next';

type GroupsIndexData = {
    groupTreeItems: Array<App.Data.GroupTreeItem>;
    groups: Array<App.Data.GroupData>;
    canCreateRootGroup: boolean;
    selectedGroup: App.Data.GroupData;
    polygon: App.ValueObjects.Polygon;
    canEditGroup: boolean;
    canCreateGroups: boolean;
};

const props = defineProps<GroupsIndexData>();

const showCreateModal = ref(false);
const selectedTabIndex = ref(0);

const onTabSelectionChanged = (e: any) => {
    const newIndex = e.component.option('selectedIndex');
    selectedTabIndex.value = newIndex;
    window.location.hash = `tab=${newIndex}`;
};

// Function to get tab index from URL hash
const getTabIndexFromHash = (): number => {
    const hash = window.location.hash;
    if (hash && hash.includes('tab=')) {
        const tabIndex = parseInt(hash.split('tab=')[1], 10);
        return !isNaN(tabIndex) ? tabIndex : 0;
    }
    return 0;
};

// Initialize tab index from URL on component mount
onMounted(() => {
    selectedTabIndex.value = getTabIndexFromHash();
});

const polygon = computed<App.ValueObjects.Polygon>(() => {
    if (props.polygon === null) {
        return {
            coordinates: [],
        };
    }
    return props.polygon;
});
</script>
