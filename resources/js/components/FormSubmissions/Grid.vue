<template>
	<div class="flex items-center justify-between mb-4">
		<div></div>
		<Badge class="bg-white" variant="secondary">
			{{pagination.total}} {{pagination.total === 1 ? 'Eintrag' : 'Einträge'}}
		</Badge>
	</div>

	<div class="flex flex-wrap gap-2">
		<SubmissionCard
			v-for="(submission, index) in formSubmissions"
			:key="submission.id"
			:submission="submission"
			:index="index"
            :show-group-header="props.groupByForm && isFirstInGroup(index)"
            :show-group-badge="! props.groupByForm"
		/>
		<WhenVisible
			always
			:params="{
				data:  {
					page: pagination.currentPage + 1,
				},
				only: ['formSubmissions', 'pagination'],
				preserveUrl: true,
			}"
		>
		<div v-if="pagination.currentPage >= pagination.lastPage">
			Ende der Liste
		</div>
		<template #fallback>
			<div>Loading...</div>
		</template>
	</WhenVisible>
	</div>
</template>
<script lang="ts" setup>
import Badge from '@/shadcn/components/ui/badge/Badge.vue';
import SubmissionCard from '@/components/FormSubmissions/SubmissionCard.vue';
import { WhenVisible } from '@inertiajs/vue3';

const props = defineProps<{
	formSubmissions: App.Data.FormSubmissionData[] | Record<number, App.Data.FormSubmissionData>
	pagination: App.Data.PaginationData;
    groupByForm: boolean;
}>();

function isFirstInGroup(index: number) {
	return index === 0 || props.formSubmissions[index].form_name !== props.formSubmissions[index - 1].form_name;
}

</script>
