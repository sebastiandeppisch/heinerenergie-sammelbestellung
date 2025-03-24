<template>
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Title section -->
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-semibold text-gray-800">Initiativen-Struktur</h2>
          <div class="flex items-center gap-4">
            <DxButton
              v-if="canCreateGroups"
              text="Neue Initiative anlegen"
              icon="plus"
              stylingMode="contained"
              @click="showCreateModal = true"
            />
          </div>
        </div>

        <!-- Content section -->
        <div class="flex gap-6">
          <!-- Tree view card -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg shrink-0 w-180">
            <div class="p-6">
              <GroupTree
                :groups="groups"
                :selected-group="selectedGroup"
              />
            </div>
          </div>

          <!-- Details card -->
          <div v-if="selectedGroup" class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex-grow">
            <div class="p-6">
              <DxTabPanel
                :selected-index="selectedTabIndex"
                @selection-changed="onTabSelectionChanged"
              >
                <DxItem
                  title="Stammdaten"
                  icon="info"
                >
                  <template #default>
                    <GroupDetails
                      :group="selectedGroup"
                      :can-edit="canEditGroup"
                    />
                  </template>
                </DxItem>
                <DxItem
                  title="Berater:innen"
                  icon="user"
                  v-if="canEditGroup"
                >
                  <template #default>
                    <GroupUsers
                      :group="selectedGroup"
                    />
                  </template>
                </DxItem>
                <DxItem
                  title="Beratungsgebiet"
                  icon="map"
                >
                  <template #default>
                    <ConsultingAreaForm
                      :group="selectedGroup"
                      :polygon="polygon"
                    />
                  </template>
                </DxItem>
                <DxItem
                  title="Beratungszustände"
                  icon="tableproperties"
                  v-if="canEditGroup"
                >
                  <template #default>
                    <AdviceStatusGroup
                      :group="selectedGroup"
                      :groups="groups"
                    />
                  </template>
                </DxItem>
              </DxTabPanel>
            </div>
          </div>
          <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex-grow">
            <div class="p-6 text-gray-500 text-center">
              Bitte wähle eine Initiative aus, um die Details anzuzeigen
            </div>
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
      <CreateGroupForm
        :parent-groups="groups"
        :parent-required="!canCreateRootGroup"
        @close="showCreateModal = false"
      />
    </DxPopup>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { DxTabPanel, DxItem } from 'devextreme-vue/tab-panel'
import { DxPopup } from 'devextreme-vue/popup'
import GroupTree from '@/components/Groups/GroupTree.vue'
import GroupDetails from '@/components/Groups/GroupDetails.vue'
import GroupUsers from '@/components/Groups/GroupUsers.vue'
import ConsultingAreaForm from '@/components/ConsultingArea/ConsultingAreaForm.vue'
import AdviceStatusGroup from '@/views/AdviceStatusGroup.vue'
import { DxButton } from 'devextreme-vue'
import CreateGroupForm from '@/components/Groups/CreateGroupForm.vue'
type GroupData = App.Data.GroupData;

const props = defineProps<{
  groups: GroupData[],
  selectedGroup: GroupData | null,
  canCreateRootGroup: boolean,
  polygon: number[][] | null,
  canCreateGroups: boolean,
  canEditGroup: boolean,
}>();

const showCreateModal = ref(false)
const selectedTabIndex = ref(0)

const onTabSelectionChanged = (e: any) => {
  selectedTabIndex.value = e.component.option('selectedIndex')
}


const polygon = computed(() => {
  if (props.polygon === null) {
    return [];
  }
  return props.polygon
})

</script>
