<script setup lang="ts">
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';

defineProps<{
    categories: Array<App.Data.MapPointCategoryData>;
    idPrefix: string;
}>();

const visibility = defineModel<Record<string, boolean>>('visibility', { required: true });
</script>

<template>
    <div class="space-y-2">
        <div v-for="category in categories" :key="category.id" class="flex items-center gap-2">
            <Checkbox :id="idPrefix + category.id" v-model="visibility[category.id]" />
            <Label :for="idPrefix + category.id" class="flex items-center gap-2 text-sm font-normal">
                <div v-if="category.image_path" class="h-5 w-5 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                    <img :src="category.image_path" :alt="category.name" class="h-full w-full object-cover" />
                </div>
                {{ category.name }}
            </Label>
        </div>
    </div>
</template>
