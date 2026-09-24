<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { FileUp, Upload } from '@lucide/vue';
import { route } from 'ziggy-js';

defineProps<{
    upload: App.Data.SpreadsheetUploadData | null;
}>();

const uploadForm = useForm({ file: null as File | null });

function uploadFile(event: Event) {
    const input = event.target as HTMLInputElement;
    uploadForm.file = input.files?.[0] ?? null;

    if (uploadForm.file) {
        uploadForm.post(route('mappoints.import.upload'), { forceFormData: true });
    }
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>1. Datei hochladen</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <p class="text-sm text-gray-600">
                Unterstützt werden CSV, Excel (.xlsx, .xls) und OpenDocument (.ods). Die erste Zeile muss die Spaltenüberschriften enthalten.
            </p>
            <div class="flex items-center gap-3">
                <Button as="label" variant="outline" class="cursor-pointer" :disabled="uploadForm.processing">
                    <Upload />
                    {{ upload ? 'Andere Datei wählen' : 'Datei wählen' }}
                    <input type="file" accept=".csv,.txt,.xls,.xlsx,.ods" class="hidden" @change="uploadFile" />
                </Button>
                <span v-if="uploadForm.processing" class="text-sm text-gray-500">Wird hochgeladen...</span>
                <span v-else-if="upload" class="text-sm text-gray-700">
                    <FileUp class="mr-1 inline h-4 w-4" />
                    {{ upload.filename }} · {{ upload.row_count }} Zeilen
                </span>
            </div>
            <p v-if="uploadForm.errors.file" class="text-sm text-red-500" data-test="upload-error">{{ uploadForm.errors.file }}</p>
        </CardContent>
    </Card>
</template>
