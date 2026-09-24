<script setup lang="ts">
import ImportSession from '@/components/MapPointImport/ImportSession.vue';
import UploadCard from '@/components/MapPointImport/UploadCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import { setLayoutProps } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{
    mappings: Array<App.Data.MapPointSpreadsheetMappingData>;
    fields: Array<App.Data.MapPointSpreadsheetFieldData>;
    upload: App.Data.SpreadsheetUploadData | null;
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte', href: route('mappoints.index') }, { title: 'Importieren' }],
});
</script>

<template>
    <div class="mx-auto w-full max-w-5xl">
        <PageHeader title="Kartenpunkte importieren" />

        <div class="space-y-6">
            <UploadCard :upload="upload" />

            <!-- Keyed by the upload: a new file starts a new session, so mapping and preview never outlive their file. -->
            <ImportSession v-if="upload" :key="upload.token" :upload="upload" :fields="fields" :mappings="mappings" />
        </div>
    </div>
</template>
