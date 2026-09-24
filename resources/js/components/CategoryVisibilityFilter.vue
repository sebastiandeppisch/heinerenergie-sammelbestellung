<script setup lang="ts">
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';
import { ancestorIds, flattenCategoryTree, isIncludedByAncestor } from '@/utils/categoryTree';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        categories: Array<App.Data.MapPointCategoryData>;
        idPrefix: string;
        /**
         * Selecting a category includes its sub categories, as in an embed. Otherwise unchecking
         * a category hides its sub categories, as in a map filter.
         */
        includeDescendants?: boolean;
    }>(),
    { includeDescendants: false },
);

const visibility = defineModel<Record<string, boolean>>('visibility', { required: true });

const entries = computed(() => flattenCategoryTree(props.categories));

/**
 * Sub categories follow their parent while it decides for them: in an embed a selected parent includes them,
 * on a map an unchecked parent hides them. Their own choice is kept for when the parent no longer decides.
 */
function isDecidedByAncestor(categoryId: string): boolean {
    if (props.includeDescendants) {
        return isIncludedByAncestor(props.categories, visibility.value, categoryId);
    }

    return ancestorIds(props.categories, categoryId).some((id) => !visibility.value[id]);
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="{ category, depth } in entries"
            :key="category.id"
            class="flex items-center gap-2"
            :style="{ paddingLeft: `${depth * 1.25}rem` }"
            :data-test="`category-filter-${category.id}`"
        >
            <Checkbox v-if="isDecidedByAncestor(category.id)" :id="idPrefix + category.id" :model-value="includeDescendants" disabled />
            <Checkbox v-else :id="idPrefix + category.id" v-model="visibility[category.id]" />
            <Label
                :for="idPrefix + category.id"
                class="flex items-center gap-2 text-sm font-normal"
                :class="{ 'text-muted-foreground': isDecidedByAncestor(category.id) }"
            >
                <div v-if="category.marker_image_path" class="h-5 w-5 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                    <img :src="category.marker_image_path" :alt="category.name" class="h-full w-full object-cover" />
                </div>
                {{ category.name }}
            </Label>
        </div>
    </div>
</template>
