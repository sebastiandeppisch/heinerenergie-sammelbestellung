<template>
    <div>
        <div class="mb-4 flex items-center justify-end">
            <Badge class="bg-white" variant="secondary"> {{ pagination.total }} {{ pagination.total === 1 ? 'Eintrag' : 'Einträge' }} </Badge>
        </div>

        <div class="rounded-md border bg-white">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[1%] whitespace-nowrap">Aktionen</TableHead>
                        <TableHead>Datum</TableHead>
                        <TableHead>Status</TableHead>

                        <template v-if="isSingleForm">
                            <TableHead v-for="column in dynamicColumns" :key="column.id">
                                {{ column.label }}
                            </TableHead>
                        </template>
                        <template v-else>
                            <TableHead>Formular</TableHead>
                            <TableHead>Vorschau</TableHead>
                        </template>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="submission in submissionsList"
                        :key="submission.id"
                        class="cursor-pointer"
                        @click="openDetail(submission, submission.__key)"
                    >
                        <TableCell class="w-[1%] whitespace-nowrap" @click.stop>
                            <div class="flex gap-1">
                                <Button size="sm" variant="ghost" title="Details" @click="openDetail(submission, submission.__key)">
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="!submission.seen"
                                    size="sm"
                                    variant="ghost"
                                    title="Als gelesen markieren"
                                    @click="markSeen(submission.id)"
                                >
                                    <MailCheck class="h-4 w-4" />
                                </Button>
                                <Button v-else size="sm" variant="ghost" title="Als ungesehen markieren" @click="markUnseen(submission.id)">
                                    <MailOpen class="h-4 w-4" />
                                </Button>
                            </div>
                        </TableCell>
                        <TableCell class="whitespace-nowrap">
                            {{ formatDateTime(submission.submitted_at) }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="submission.seen ? 'secondary' : 'default'">
                                {{ submission.seen ? 'Gelesen' : 'Ungelesen' }}
                            </Badge>
                        </TableCell>
                        <template v-if="isSingleForm">
                            <TableCell v-for="column in dynamicColumns" :key="column.id">
                                {{ getFieldValue(submission, column.label) }}
                            </TableCell>
                        </template>
                        <template v-else>
                            <TableCell>{{ submission.form_name }}</TableCell>
                            <TableCell class="max-w-md text-sm text-gray-600">
                                <SubmissionPreview :submission="submission" />
                            </TableCell>
                        </template>
                    </TableRow>
                    <TableEmpty v-if="submissionsList.length === 0" :colspan="totalColumnCount"> Keine Einträge </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <SubmissionPagination v-if="pagination.lastPage > 1" :pagination="pagination" class="mt-4" />

        <SubmissionDialog v-model:open="dialogOpen" :submission="selectedSubmission" :index="selectedIndex" />
    </div>
</template>

<script lang="ts" setup>
import SubmissionDialog from '@/components/FormSubmissions/SubmissionDialog.vue';
import SubmissionPagination from '@/components/FormSubmissions/SubmissionPagination.vue';
import SubmissionPreview from '@/components/FormSubmissions/SubmissionPreview.vue';
import Badge from '@/shadcn/components/ui/badge/Badge.vue';
import Button from '@/shadcn/components/ui/button/Button.vue';
import Table from '@/shadcn/components/ui/table/Table.vue';
import TableBody from '@/shadcn/components/ui/table/TableBody.vue';
import TableCell from '@/shadcn/components/ui/table/TableCell.vue';
import TableEmpty from '@/shadcn/components/ui/table/TableEmpty.vue';
import TableHead from '@/shadcn/components/ui/table/TableHead.vue';
import TableHeader from '@/shadcn/components/ui/table/TableHeader.vue';
import TableRow from '@/shadcn/components/ui/table/TableRow.vue';
import { router } from '@inertiajs/vue3';
import { Eye, MailCheck, MailOpen } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    formSubmissions: App.Data.FormSubmissionData[] | Record<number, App.Data.FormSubmissionData>;
    formDefinitions: App.Data.FormDefinitionData[];
    selectedFormDefinitions: string[];
    pagination: App.Data.PaginationData;
}>();

const dialogOpen = ref(false);
const selectedSubmission = ref<App.Data.FormSubmissionData | null>(null);
const selectedIndex = ref<number | string>(0);

const submissionsList = computed<Array<App.Data.FormSubmissionData & { __key: number | string }>>(() => {
    const entries = Object.entries(props.formSubmissions);
    return entries.map(([key, submission]) => ({
        ...(submission as App.Data.FormSubmissionData),
        __key: isNaN(Number(key)) ? key : Number(key),
    }));
});

const isSingleForm = computed<boolean>(() => {
    return props.selectedFormDefinitions.length === 1 && singleFormDefinition.value !== null;
});

const singleFormDefinition = computed<App.Data.FormDefinitionData | null>(() => {
    if (props.selectedFormDefinitions.length !== 1) {
        return null;
    }
    return props.formDefinitions.find((definition) => definition.id === props.selectedFormDefinitions[0]) ?? null;
});

const dynamicColumns = computed<App.Data.FormFieldData[]>(() => {
    if (singleFormDefinition.value === null) {
        return [];
    }
    return [...singleFormDefinition.value.fields].sort((a, b) => a.sort_order - b.sort_order);
});

const totalColumnCount = computed<number>(() => {
    const fixedLeading = 1;
    const actions = 1;
    const middle = isSingleForm.value ? dynamicColumns.value.length : 3;
    return fixedLeading + middle + actions;
});

function formatDateTime(date: Date | string): string {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatValue(value: App.Data.SubmissionFieldData['value']): string {
    if (value === null || value === undefined) {
        return '';
    }
    if (Array.isArray(value)) {
        return value.join(', ');
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

function getFieldValue(submission: App.Data.FormSubmissionData, label: string): string {
    const match = submission.fields.find((field) => field.field.label === label);
    if (!match) {
        return '';
    }
    return formatValue(match.value);
}


function openDetail(submission: App.Data.FormSubmissionData, index: number | string) {
    selectedSubmission.value = submission;
    selectedIndex.value = index;
    dialogOpen.value = true;
}

function markSeen(id: string) {
    router.post(route('form-submissions.mark-seen', id), {}, { preserveUrl: true, preserveScroll: true });
}

function markUnseen(id: string) {
    router.post(route('form-submissions.mark-unseen', id), {}, { preserveUrl: true, preserveScroll: true });
}
</script>
