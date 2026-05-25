<script setup lang="ts">
import FormFieldRenderer from '@/components/FormBuilder/FormFieldRenderer.vue';
import Button from '@/shadcn/components/ui/button/Button.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

type ChecklistEntryData = App.Data.ChecklistEntryData;

const props = defineProps<{
    entry: ChecklistEntryData;
    adviceId: string;
}>();

const data = ref<Record<string, unknown>>(Object.fromEntries(props.entry.fields.map((f) => [f.field.id, f.value])));
const saving = ref(false);
const savedAt = ref<Date | null>(props.entry.updated_at ? new Date(props.entry.updated_at as unknown as string) : null);

function save() {
    saving.value = true;
    router.put(
        route('checklist-entries.update', { advice: props.adviceId, checklistEntry: props.entry.id }),
        { data: data.value as Record<string, string | number | boolean | null> },
        {
            preserveScroll: true,
            onSuccess: () => {
                savedAt.value = new Date();
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="checklist-entry">
        <div class="checklist-entry__fields">
            <FormFieldRenderer
                v-for="entryField in entry.fields"
                :key="entryField.field.id"
                :field="entryField.field"
                :is-preview="true"
                v-model="data[entryField.field.id]"
            />
        </div>

        <div class="checklist-entry__footer">
            <span v-if="savedAt" class="checklist-entry__saved-at"> Zuletzt gespeichert: {{ savedAt.toLocaleString('de-DE') }} </span>
            <Button @click="save" :disabled="saving" size="sm">
                {{ saving ? 'Wird gespeichert...' : 'Speichern' }}
            </Button>
        </div>
    </div>
</template>

<style scoped>
.checklist-entry {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 16px;
}

.checklist-entry__fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.checklist-entry__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    border-top: 1px solid #e9ecef;
}

.checklist-entry__saved-at {
    font-size: 12px;
    color: #6c757d;
}
</style>
