<template>
  <DxTreeView
    ref="treeViewRef"
    :items="props.groups"
    :search-enabled="true"
    :search-mode="'contains'"
    :select-by-click="true"
    display-expr="name"
    key-expr="id"
    data-structure="plain"
    selection-mode="single"
    parent-id-expr="parent_id"
    class="group-tree-container"
    :show-checkboxes-mode="true"
    @selection-changed="treeViewSelectionChanged"
  >
    <template #item="{ data: item }">
      <div class="flex items-center py-1.5 pl-1">
        <!-- Group icon -->
        <div class="mr-3 flex-shrink-0 flex items-center">
          <img
            v-if="item.logo_path"
            :src="item.logo_path"
            :alt="item.name"
            class="max-h-[2em] w-auto rounded object-contain"
          />
          <svg
            v-else
            class="h-[1.5em] w-[1.5em] text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
        </div>
        <!-- Group name and counts -->
        <div class="flex items-center justify-between w-full min-w-0">
          <span class="font-medium text-sm truncate">{{ item.name }}</span>
          <span class="text-xs text-gray-500 ml-4 whitespace-nowrap">
            ({{ item.users_count }} Berater:innen, {{ item.advices_count }} Beratungen)
          </span>
        </div>
      </div>
    </template>
  </DxTreeView>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DxTreeView } from 'devextreme-vue/tree-view'
import { route } from 'ziggy-js'
import { router } from '@inertiajs/vue3'

type GroupTreeItem = App.Data.GroupTreeItem;
type GroupData = App.Data.GroupData;

const props = defineProps<{
  groups: GroupTreeItem[]
  selectedGroup: GroupData | null,
}>()

const treeViewRef = ref<InstanceType<typeof DxTreeView> | null>(null)
function treeViewSelectionChanged() {
  const selectedNodes = treeViewRef.value.instance.getSelectedNodes()
  router.visit(route('groups.show', selectedNodes[0].itemData.id))
}

</script>
