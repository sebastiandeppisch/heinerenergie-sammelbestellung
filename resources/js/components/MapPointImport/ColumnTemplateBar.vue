<script setup lang="ts">
import type { MapPointImportColumn } from '@/composables/useMapPointColumnMapping';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mappings: Array<App.Data.MapPointSpreadsheetMappingData>;
    columns: Array<MapPointImportColumn>;
    keyField: App.Enums.MapPointSpreadsheetField;
}>();

const emit = defineEmits<{
    apply: [template: App.Data.MapPointSpreadsheetMappingData];
}>();

/**
 * Column templates belong to the initiative and outlive a single upload. They are loaded into the
 * mapping here and saved from it.
 */
const selectedId = ref<string | null>(null);
const name = ref('');

const selected = computed(() => props.mappings.find((template) => template.id === selectedId.value) ?? null);

watch(selected, (template) => {
    if (template) {
        emit('apply', template);
        name.value = template.name;
    }
});

const form = useForm({});

function save(asNewTemplate: boolean) {
    const template = { name: name.value, key_field: props.keyField, columns: props.columns };
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedId.value = props.mappings.find((existing) => existing.name === template.name)?.id ?? null;
        },
    };

    form.transform(() => template);

    if (!asNewTemplate && selected.value) {
        form.put(route('mappoints.spreadsheet-mappings.update', selected.value.id), options);
    } else {
        form.post(route('mappoints.spreadsheet-mappings.store'), options);
    }
}

function remove() {
    if (!selected.value || !confirm(`Soll die Spaltenvorlage „${selected.value.name}“ gelöscht werden?`)) {
        return;
    }

    form.delete(route('mappoints.spreadsheet-mappings.destroy', selected.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedId.value = null;
            name.value = '';
        },
    });
}
</script>

<template>
    <div class="space-y-3 rounded-lg border bg-gray-50 p-4">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2" data-test="template-picker">
                <Label for="column_template">Spaltenvorlage laden</Label>
                <Select id="column_template" v-model="selectedId">
                    <SelectTrigger class="w-full bg-white">
                        <SelectValue placeholder="Keine Vorlage" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="template in mappings" :key="template.id" :value="template.id">{{ template.name }}</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="mappings.length === 0" class="text-xs text-gray-500">Für diese Initiative gibt es noch keine Spaltenvorlagen.</p>
            </div>

            <div class="space-y-2">
                <Label for="column_template_name">Als Spaltenvorlage speichern</Label>
                <Input id="column_template_name" v-model="name" placeholder="Name der Vorlage" class="bg-white" data-test="template-name" />
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button variant="outline" :disabled="!name || form.processing" data-test="save-template" @click="save(true)">
                <Save />
                Als neue Vorlage speichern
            </Button>
            <Button v-if="selected" variant="outline" :disabled="!name || form.processing" data-test="update-template" @click="save(false)">
                <Save />
                „{{ selected.name }}“ aktualisieren
            </Button>
            <Button v-if="selected" variant="destructive" data-test="delete-template" @click="remove">
                <Trash2 />
                Löschen
            </Button>
        </div>

        <p class="text-xs text-gray-500">
            Spaltenvorlagen gelten für die ganze Initiative. Beim Export bestimmen sie, welche Spalten die Datei enthält.
        </p>
        <p v-for="(message, field) in form.errors" :key="field" class="text-sm text-red-500" data-test="template-error">{{ message }}</p>
    </div>
</template>
