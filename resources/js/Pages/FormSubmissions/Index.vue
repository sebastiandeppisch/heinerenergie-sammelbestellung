<template>
    <div class="m-6">
        <h2>Formulareinträge</h2>

        <Filter
            :form-definitions="formDefinitions"
            v-model:selected-form-types="selectedFormDefinitionsModel"
            v-model:date-from="dateFromModel"
            v-model:date-to="dateToModel"
            v-model:sort-order="sortOrderModel"
            v-model:group-by-form="groupByFormModel"
        />

        <Tabs v-model="viewModel" class="w-full">
            <TabsList>
                <TabsTrigger value="cards">Kachelansicht</TabsTrigger>
                <TabsTrigger value="table">Tabellenansicht</TabsTrigger>
            </TabsList>
            <TabsContent value="cards">
                <Grid :form-submissions="formSubmissions" :pagination="props.pagination" :group-by-form="groupByFormModel" />
            </TabsContent>
            <TabsContent value="table">
                <SubmissionsTable
                    :form-submissions="formSubmissions"
                    :form-definitions="formDefinitions"
                    :selected-form-definitions="selectedFormDefinitions"
                    :pagination="props.pagination"
                />
            </TabsContent>
        </Tabs>
    </div>
</template>
<script lang="ts" setup>
import Filter from '@/components/FormSubmissions/Filter.vue';
import Grid from '@/components/FormSubmissions/Grid.vue';
import SubmissionsTable from '@/components/FormSubmissions/SubmissionsTable.vue';
import Tabs from '@/shadcn/components/ui/tabs/Tabs.vue';
import TabsContent from '@/shadcn/components/ui/tabs/TabsContent.vue';
import TabsList from '@/shadcn/components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/shadcn/components/ui/tabs/TabsTrigger.vue';
import { router } from '@inertiajs/vue3';
import { computed, type WritableComputedRef } from 'vue';

const props = defineProps<{
    formDefinitions: Array<App.Data.FormDefinitionData>;
    dateFrom: Date | null;
    dateTo: Date | null;
    selectedFormDefinitions: string[];
    sortOrder: 'asc' | 'desc';
    groupByForm: boolean;
    view: 'cards' | 'table';
    formSubmissions: App.Data.FormSubmissionData[] | any;
    pagination: App.Data.PaginationData<App.Data.FormSubmissionData>;
}>();

const filter = computed(() => {
    const result = {} as any;
    if (props.dateFrom !== null) {
        result['dateFrom'] = props.dateFrom;
    }
    if (props.dateTo !== null) {
        result['dateTo'] = props.dateTo;
    }
    if (props.selectedFormDefinitions.length > 0) {
        result['selectedFormDefinitions'] = props.selectedFormDefinitions;
    }
    if (props.sortOrder === 'asc') {
        result['sortOrder'] = props.sortOrder;
    } else {
        result['sortOrder'] = undefined;
    }
    if (props.groupByForm) {
        result['groupByForm'] = props.groupByForm;
    }
    if (props.view === 'table') {
        result['view'] = props.view;
    }
    return result;
});

function filterQuery(query: any) {
    const result = {} as any;
    if (query.dateFrom !== null) {
        result['dateFrom'] = query.dateFrom;
    } else {
        result['dateFrom'] = undefined;
    }
    if (query.dateTo !== null) {
        result['dateTo'] = query.dateTo;
    } else {
        result['dateTo'] = undefined;
    }
    if (query.selectedFormDefinitions && query.selectedFormDefinitions.length > 0) {
        result['selectedFormDefinitions'] = query.selectedFormDefinitions;
    } else {
        result['selectedFormDefinitions'] = undefined;
    }
    if (query.sortOrder === 'asc') {
        result['sortOrder'] = query.sortOrder;
    } else {
        result['sortOrder'] = undefined;
    }
    if (query.groupByForm) {
        result['groupByForm'] = query.groupByForm;
    } else {
        result['groupByForm'] = undefined;
    }
    if (query.view === 'table') {
        result['view'] = query.view;
    } else {
        result['view'] = undefined;
    }
    return result;
}

function computedTriggerReload<T>(key: keyof typeof props): WritableComputedRef<T> {
    return computed<T>({
        get: () => props[key] as T,
        set: (value: T) => {
            const data = filterQuery({
                ...filter.value,
                [key]: value,
            });
            router.reload({
                data,
            });
        },
    });
}

const dateFromModel = computedTriggerReload<Date | null>('dateFrom');
const dateToModel = computedTriggerReload<Date | null>('dateTo');
const selectedFormDefinitionsModel = computedTriggerReload<string[]>('selectedFormDefinitions');
const sortOrderModel = computedTriggerReload<'asc' | 'desc'>('sortOrder');
const groupByFormModel = computedTriggerReload<boolean>('groupByForm');
const viewModel = computedTriggerReload<'cards' | 'table'>('view');
</script>
