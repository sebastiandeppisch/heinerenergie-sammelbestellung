<script setup lang="ts">
import ColumnMappingCard from '@/components/MapPointImport/ColumnMappingCard.vue';
import ColumnTemplateBar from '@/components/MapPointImport/ColumnTemplateBar.vue';
import ImportPreviewCard from '@/components/MapPointImport/ImportPreviewCard.vue';
import { useMapPointColumnMapping } from '@/composables/useMapPointColumnMapping';

const props = defineProps<{
    upload: App.Data.SpreadsheetUploadData;
    fields: Array<App.Data.MapPointSpreadsheetFieldData>;
    mappings: Array<App.Data.MapPointSpreadsheetMappingData>;
}>();

/**
 * The work on one uploaded file: mapping its columns and running the import with that mapping.
 * The session owns the mapping, the cards only show and edit it.
 */
const { columnFields, keyField, defaultVisibility, columns, payload, applyTemplate } = useMapPointColumnMapping(props.upload, props.fields);
</script>

<template>
    <div class="space-y-6">
        <ColumnMappingCard
            v-model:column-fields="columnFields"
            v-model:key-field="keyField"
            v-model:default-visibility="defaultVisibility"
            :upload="upload"
            :fields="fields"
        >
            <template #templates>
                <ColumnTemplateBar :mappings="mappings" :columns="columns" :key-field="keyField" @apply="applyTemplate" />
            </template>
        </ColumnMappingCard>

        <ImportPreviewCard :payload="payload" />
    </div>
</template>
