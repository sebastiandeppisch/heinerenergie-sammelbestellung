<template>
    <div class="py-12">
        <div class="mx-auto space-y-6 sm:px-6 lg:px-8">
            <!-- Title section -->
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Initiativen-Struktur</h2>
                <div class="flex items-center gap-4">
                    <Button v-if="canCreateGroups" variant="default" @click="showCreateModal = true">
                        <Plus class="h-4 w-4" />
                        Neue Initiative anlegen
                    </Button>
                </div>
            </div>

            <!-- Content section -->
            <div class="flex gap-6">
                <!-- Tree view card -->
                <div class="shrink-0 grow-1 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <GroupTree :groups="props.groupTreeItems" :selected-group="selectedGroup" />
                    </div>
                </div>

                <!-- Details card -->
                <div v-if="selectedGroup" class="flex-grow grow-2 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <Tabs v-model="selectedTab" @update:model-value="onTabChanged">
                            <TabsList class="grid w-full grid-cols-5" v-if="canEditGroup">
                                <TabsTrigger value="stammdaten" class="flex items-center gap-2">
                                    <Info class="h-4 w-4" />
                                    Stammdaten
                                </TabsTrigger>
                                <TabsTrigger value="berater" class="flex items-center gap-2">
                                    <Users class="h-4 w-4" />
                                    Berater:innen
                                </TabsTrigger>
                                <TabsTrigger value="beratungsgebiet" class="flex items-center gap-2">
                                    <Map class="h-4 w-4" />
                                    Beratungsgebiet
                                </TabsTrigger>
                                <TabsTrigger value="beratungszustaende" class="flex items-center gap-2">
                                    <Table class="h-4 w-4" />
                                    Beratungszustände
                                </TabsTrigger>
                                <TabsTrigger value="email" class="flex items-center gap-2">
                                    <Mail class="h-4 w-4" />
                                    E-Mail
                                </TabsTrigger>
                            </TabsList>
                            <TabsList class="grid w-full grid-cols-2" v-else>
                                <TabsTrigger value="stammdaten" class="flex items-center gap-2">
                                    <Info class="h-4 w-4" />
                                    Stammdaten
                                </TabsTrigger>
                                <TabsTrigger value="beratungsgebiet" class="flex items-center gap-2">
                                    <Map class="h-4 w-4" />
                                    Beratungsgebiet
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="stammdaten">
                                <GroupDetails :group="selectedGroup" :can-edit="canEditGroup" />
                            </TabsContent>
                            <TabsContent value="berater" v-if="canEditGroup">
                                <div class="flex justify-end pt-4 pb-2">
                                    <Button variant="outline" size="sm" :as-child="true">
                                        <a :href="route('groups.nextcloud', selectedGroup.id)">
                                            <Cloud class="mr-1 h-4 w-4" />
                                            Nextcloud-Abgleich
                                        </a>
                                    </Button>
                                </div>
                                <GroupUsers :group="selectedGroup" :group-users="groupUsers" :all-users="allUsers" />
                            </TabsContent>
                            <TabsContent value="beratungsgebiet">
                                <ConsultingAreaForm
                                    :group="selectedGroup"
                                    :polygon="polygon"
                                    :consulting-area-postal-codes="consultingAreaPostalCodes"
                                />
                            </TabsContent>
                            <TabsContent value="beratungszustaende" v-if="canEditGroup">
                                <AdviceStatusGroup :group="selectedGroup" :groups="groups" />
                            </TabsContent>
                            <TabsContent value="email" v-if="canEditGroup">
                                <GroupNewAdviceMail :group="selectedGroup" :can-edit="canEditGroup" />
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>
                <div v-else class="flex-grow overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500">Bitte wähle eine Initiative aus, um die Details anzuzeigen</div>
                </div>
            </div>
        </div>
    </div>
    <Dialog v-model:open="showCreateModal">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>Neue Initiative anlegen</DialogTitle>
            </DialogHeader>
            <CreateGroupForm :parent-groups="groups" :parent-required="!canCreateRootGroup" @close="showCreateModal = false" />
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import ConsultingAreaForm from '@/components/ConsultingArea/ConsultingAreaForm.vue';
import CreateGroupForm from '@/components/Groups/CreateGroupForm.vue';
import GroupDetails from '@/components/Groups/GroupDetails.vue';
import GroupNewAdviceMail from '@/components/Groups/GroupNewAdviceMail.vue';
import GroupTree from '@/components/Groups/GroupTree.vue';
import GroupUsers from '@/components/Groups/GroupUsers.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import AdviceStatusGroup from '@/views/AdviceStatusGroup.vue';
import { Cloud, Info, Mail, Map, Plus, Table, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type GroupsIndexData = {
    groupTreeItems: Array<App.Data.GroupTreeItem>;
    groups: Array<App.Data.GroupData>;
    canCreateRootGroup: boolean;
    selectedGroup: App.Data.GroupData;
    polygon: App.ValueObjects.Polygon;
    consultingAreaPostalCodes: string[];
    canEditGroup: boolean;
    canCreateGroups: boolean;
    groupUsers?: any;
    allUsers: Array<{
        id: string;
        name: string;
        email: string;
    }>;
};

const props = defineProps<GroupsIndexData>();

const showCreateModal = ref(false);

// Tab values mapping
const tabValues = ['stammdaten', 'berater', 'beratungsgebiet', 'beratungszustaende', 'email'];
const tabValuesWithoutEdit = ['stammdaten', 'beratungsgebiet'];

const selectedTab = ref<string>('stammdaten');

const onTabChanged = (value: string | number) => {
    const tabValue = String(value);
    selectedTab.value = tabValue;
    const tabIndex = canEditGroup.value ? tabValues.indexOf(tabValue) : tabValuesWithoutEdit.indexOf(tabValue);
    if (tabIndex !== -1) {
        window.location.hash = `tab=${tabIndex}`;
    }
};

// Function to get tab value from URL hash
const getTabValueFromHash = (): string => {
    const hash = window.location.hash;
    if (hash && hash.includes('tab=')) {
        const tabIndex = parseInt(hash.split('tab=')[1], 10);
        if (!isNaN(tabIndex)) {
            if (canEditGroup.value && tabIndex < tabValues.length) {
                return tabValues[tabIndex];
            } else if (!canEditGroup.value && tabIndex < tabValuesWithoutEdit.length) {
                return tabValuesWithoutEdit[tabIndex];
            }
        }
    }
    return canEditGroup.value ? tabValues[0] : tabValuesWithoutEdit[0];
};

const canEditGroup = computed(() => props.canEditGroup);

// Initialize tab value from URL on component mount
onMounted(() => {
    selectedTab.value = getTabValueFromHash();
});

// Watch for hash changes
watch(
    () => window.location.hash,
    () => {
        selectedTab.value = getTabValueFromHash();
    },
);

const polygon = computed<App.ValueObjects.Polygon>(() => {
    if (props.polygon === null) {
        return {
            coordinates: [],
        };
    }
    return props.polygon;
});
</script>
