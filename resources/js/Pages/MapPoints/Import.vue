<script setup lang="ts">
import ImportSession from '@/components/MapPointImport/ImportSession.vue';
import UploadCard from '@/components/MapPointImport/UploadCard.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { route } from 'ziggy-js';

defineProps<{
    mappings: Array<App.Data.MapPointSpreadsheetMappingData>;
    fields: Array<App.Data.MapPointSpreadsheetFieldData>;
    upload: App.Data.SpreadsheetUploadData | null;
}>();
</script>

<template>
    <div class="container mx-auto py-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <Link :href="route('mappoints.index')">
                    <Button variant="outline"><ArrowLeft />Zurück</Button>
                </Link>
                <h1 class="text-2xl font-bold">Kartenpunkte importieren</h1>
            </div>

            <UploadCard :upload="upload" />

            <!-- Keyed by the upload: a new file starts a new session, so mapping and preview never outlive their file. -->
            <ImportSession v-if="upload" :key="upload.token" :upload="upload" :fields="fields" :mappings="mappings" />
        </div>
    </div>
</template>
