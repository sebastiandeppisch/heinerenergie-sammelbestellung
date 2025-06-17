
<template>
    <div class="m-6">
        <h2>Formulareinträge</h2>

        <Filter
            :form-definitions="formDefinitions"

            v-model:selected-form-types="selectedFormDefinitions"
            v-model:date-from="dateFrom"
            v-model:date-to="dateTo"
            v-model:sort-order="sortOrder"
            v-model:group-by-form="groupByForm"
        />

        {{  selectedFormDefinitions  }}
    </div>
</template>
<script lang="ts" setup>
import Filter from '@/components/FormSubmissions/Filter.vue';
import { computed, ComputedRef } from 'vue';
import { router } from '@inertiajs/vue3';
const props = defineProps<{
    formDefinitions: Array<App.Data.FormDefinitionData>
    dateFrom: Date | null
    dateTo: Date | null
    selectedFormDefinitions: string[]
    sortOrder: 'asc' | 'desc'
    groupByForm: boolean
}>();

const filter = computed(() => {
    const result = {} as any;
    if( props.dateFrom !== null) {
        result['dateFrom'] = props.dateFrom;
    }
    if( props.dateTo !== null) {
        result['dateTo'] = props.dateTo;
    }
    if( props.selectedFormDefinitions.length > 0) {
        result['selectedFormDefinitions'] = props.selectedFormDefinitions;
    }
    if( props.sortOrder === 'asc') {
        result['sortOrder'] = props.sortOrder;  
    }else{
        result['sortOrder'] = undefined;
    }
    if( props.groupByForm) {
        result['groupByForm'] = props.groupByForm;
    }
    return result;
});

function filterQuery(query: any){
    const result = {} as any;
    if( query.dateFrom !== null) {
        result['dateFrom'] = query.dateFrom;
    }else{
        result['dateFrom'] = undefined;
    }
    if( query.dateTo !== null) {
        result['dateTo'] = query.dateTo;
    }else{
        result['dateTo'] = undefined;
    }
    if( query.selectedFormDefinitions && query.selectedFormDefinitions.length > 0) {
        result['selectedFormDefinitions'] = query.selectedFormDefinitions;
    }else{
        result['selectedFormDefinitions'] = undefined;
    }
    if( query.sortOrder === 'asc') {
        result['sortOrder'] = query.sortOrder;
    }else{
        result['sortOrder'] = undefined;
    }
    if( query.groupByForm) {
        result['groupByForm'] = query.groupByForm;
    }else{
        result['groupByForm'] = undefined;
    }
    console.log(result);
    return result;
}

function computedTriggerReload<T>(key: keyof typeof props): ComputedRef<T> {
    // @ts-ignore
    return computed({
        get: () => props[key],
        set: (value: T) => {
            console.log(value);
            const data = filterQuery({
                ...filter.value,
                [key]: value
            });
            router.reload({
                data
            });
        }
    });
}

const dateFrom = computedTriggerReload<Date | null>('dateFrom');

const dateTo = computedTriggerReload<Date | null>('dateTo');
const selectedFormDefinitions = computedTriggerReload<string[]>('selectedFormDefinitions');
const sortOrder = computedTriggerReload<'asc' | 'desc'>('sortOrder');
const groupByForm = computedTriggerReload<boolean>('groupByForm');


</script>
