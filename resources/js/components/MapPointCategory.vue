<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    category_id: string;
    allCategories: Array<App.Data.MapPointCategoryData>;
    showName: boolean;
}>();

const category = computed(() => props.allCategories.find((category) => category.id === props.category_id));
</script>
<template>
    <div v-if="category?.image_path" class="h-6 w-6 flex-shrink-0 overflow-hidden rounded bg-gray-100">
        <img :src="category?.image_path" :alt="category?.name" class="h-full w-full object-cover" />
    </div>
    <span v-if="props.showName">{{ category?.name }}</span>
</template>
