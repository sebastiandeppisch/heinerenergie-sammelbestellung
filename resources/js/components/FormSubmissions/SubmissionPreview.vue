<script setup lang="ts">
import { computed } from 'vue';
import SubmissionFieldPreview from './SubmissionFieldPreview.vue';

const props = defineProps<{
    submission: App.Data.FormSubmissionData;
}>();

const previewFields = computed(() => {
    const result: App.Data.SubmissionFieldData[] = [];
    for (const field of props.submission.fields) {
        const val = field.value;
        if (val === null || val === undefined) continue;
        if (typeof val === 'string' && val.trim() === '') continue;
        if (Array.isArray(val) && val.length === 0) continue;
        result.push(field);
        if (result.length >= 3) break;
    }
    return result;
});
</script>

<template>
    <span class="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
        <template v-for="(field, i) in previewFields" :key="field.field.id">
            <span v-if="i > 0" class="text-gray-300 select-none" aria-hidden="true">·</span>
            <SubmissionFieldPreview :submission-field="field" />
        </template>
    </span>
</template>
