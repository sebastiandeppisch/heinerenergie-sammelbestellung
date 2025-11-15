<template>
    <div class="group-tree-container">
        <TreeRoot
            v-model="selectedItem"
            :items="rootItems"
            :get-key="(item) => item.id"
            :get-children="getChildren"
            :default-expanded="defaultExpanded"
            @update:model-value="handleSelection"
        >
            <template #default="{ flattenItems }">
                <ul class="space-y-1">
                    <TreeItem
                        v-for="item in flattenItems"
                        :key="item._id"
                        v-bind="item.bind"
                        :value="item.value"
                        :level="item.level"
                        class="group-tree-item"
                    >
                        <template #default="{ isExpanded, isSelected, handleToggle, handleSelect }">
                            <div
                                class="flex items-center py-1.5 pl-1 cursor-pointer hover:bg-gray-50 rounded"
                                :class="{ 'bg-blue-50': isSelected }"
                                @click="handleSelect"
                            >
                                <!-- Expand/Collapse icon -->
                                <button
                                    v-if="hasChildren(item.value)"
                                    @click.stop="handleToggle"
                                    class="mr-1 flex h-4 w-4 items-center justify-center"
                                >
                                    <svg
                                        class="h-3 w-3 transition-transform"
                                        :class="{ 'rotate-90': isExpanded }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <div v-else class="mr-1 w-4"></div>

                                <!-- Group icon -->
                                <div class="mr-3 flex flex-shrink-0 items-center">
                                    <img
                                        v-if="item.value.logo_path"
                                        :src="item.value.logo_path"
                                        :alt="item.value.name"
                                        class="max-h-[2em] w-auto rounded object-contain"
                                    />
                                    <svg v-else class="h-[1.5em] w-[1.5em] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                </div>
                                <!-- Group name and counts -->
                                <div class="flex w-full min-w-0 items-center justify-between">
                                    <span class="truncate text-sm font-medium">{{ item.value.name }}</span>
                                </div>
                            </div>
                        </template>
                    </TreeItem>
                </ul>
            </template>
        </TreeRoot>
    </div>
</template>

<script setup lang="ts">
import { TreeItem, TreeRoot } from 'reka-ui';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type GroupTreeItem = App.Data.GroupTreeItem;
type GroupData = App.Data.GroupData;

const props = defineProps<{
    groups: GroupTreeItem[];
    selectedGroup: GroupData | null;
}>();

const selectedItem = ref<Record<string, any> | undefined>(
    props.selectedGroup ? (props.selectedGroup as unknown as Record<string, any>) : undefined
);

// Get root items (items without parent_id or with null parent_id)
const rootItems = computed(() => {
    return props.groups.filter((item) => !item.parent_id);
});

// Get children for a given item
const getChildren = (item: GroupTreeItem): GroupTreeItem[] | undefined => {
    const children = props.groups.filter((group) => group.parent_id === item.id);
    return children.length > 0 ? children : undefined;
};

// Check if item has children
const hasChildren = (item: GroupTreeItem): boolean => {
    return props.groups.some((group) => group.parent_id === item.id);
};

// Get default expanded items (items that should be expanded initially)
const defaultExpanded = computed(() => {
    const expanded: string[] = [];
    if (props.selectedGroup) {
        // Expand path to selected group
        let current: GroupTreeItem | undefined = props.groups.find((g) => g.id === props.selectedGroup?.id);
        while (current?.parent_id) {
            expanded.push(current.parent_id);
            current = props.groups.find((g) => g.id === current?.parent_id);
        }
    }
    // Also expand items that are marked as expanded
    props.groups.forEach((item) => {
        if (item.expanded && !expanded.includes(item.id)) {
            expanded.push(item.id);
        }
    });
    return expanded;
});

// Handle selection
const handleSelection = (value: Record<string, any> | undefined) => {
    if (value && value.id) {
        router.visit(route('groups.show', value.id));
    }
};
</script>

<style scoped>
.group-tree-container {
    width: 100%;
}

.group-tree-item {
    list-style: none;
}
</style>
