<template>
    <div class="mb-4 flex items-center justify-between">
        <div></div>
        <Badge class="bg-white" variant="secondary"> {{ pagination.total }} {{ pagination.total === 1 ? 'Eintrag' : 'Einträge' }} </Badge>
    </div>

    <div class="flex flex-wrap gap-2">
        <SubmissionCard
            v-for="(submission, index) in formSubmissions"
            :key="submission.id"
            :submission="submission"
            :index="index"
            :show-group-header="props.groupByForm && isFirstInGroup(index)"
            :show-group-badge="!props.groupByForm"
        />
    </div>

    <SubmissionPagination v-if="pagination.lastPage > 1" :pagination="pagination" class="mt-4" />
</template>
<script lang="ts" setup>
import SubmissionCard from '@/components/FormSubmissions/SubmissionCard.vue';
import SubmissionPagination from '@/components/FormSubmissions/SubmissionPagination.vue';
import Badge from '@/shadcn/components/ui/badge/Badge.vue';

const props = defineProps<{
    formSubmissions: App.Data.FormSubmissionData[] | Record<number, App.Data.FormSubmissionData>;
    pagination: App.Data.PaginationData<App.Data.FormSubmissionData>;
    groupByForm: boolean;
}>();

function isFirstInGroup(index: number | string) {
    const i = Number(index);
    if (i === 0) {
        return true;
    }
    const list = props.formSubmissions as Record<number, App.Data.FormSubmissionData>;
    return list[i].form_name !== list[i - 1]?.form_name;
}
</script>
