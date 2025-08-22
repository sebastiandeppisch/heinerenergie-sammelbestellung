<script setup lang="ts">
import { useSortable } from '@vueuse/integrations/useSortable';
import { v4 as uuidv4 } from 'uuid';
import { computed, ref, useTemplateRef, watch } from 'vue';
import FormFieldRenderer from './FormFieldRenderer.vue';

const props = defineProps<{
    modelValue: App.Data.FormFieldData[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', fields: App.Data.FormFieldData[]): void;
    (e: 'field-selected', field: App.Data.FormFieldData | null): void;
}>();

const fields = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const selectedFieldId = ref<string | null>(null);
const fieldsContainer = useTemplateRef<HTMLElement>('fieldsContainer');

function selectField(field: App.Data.FormFieldData) {
    selectedFieldId.value = field.id;
    emit('field-selected', field);
    console.log('Selected field:', field);
}

function deleteField(fieldToDelete: App.Data.FormFieldData) {
    if (selectedFieldId.value === fieldToDelete.id) {
        selectedFieldId.value = null;
        emit('field-selected', null);
    }

    const updatedFields = fields.value.filter((field) => field.id !== fieldToDelete.id);
    emit('update:modelValue', updatedFields);
}

function duplicateField(fieldToDuplicate: App.Data.FormFieldData) {
    const newField = JSON.parse(JSON.stringify(fieldToDuplicate)) as App.Data.FormFieldData;

    newField.id = uuidv4();

    if (newField.options) {
        newField.options = newField.options?.map((option) => {
            option.id = uuidv4();
            return option;
        });
    }

    const updatedFields = [...fields.value, newField];
    emit('update:modelValue', updatedFields);

    selectField(newField);
}

watch(
    () => props.modelValue,
    (newFields) => {
        if (selectedFieldId.value !== null && !newFields.some((field) => field.id === selectedFieldId.value)) {
            selectedFieldId.value = null;
            emit('field-selected', null);
        }
    },
    { deep: true },
);

const { option } = useSortable(fieldsContainer, fields, {
    animation: 150,
    ghostClass: 'ghost',
    handle: '.field-drag-handle',
    draggable: '.form-field',
    group: 'form-fields',
});
</script>

<template>
    <div class="form-builder-canvas">
        <div ref="fieldsContainer" class="fields-list">
            <div
                v-for="(field, index) in fields"
                :key="field.id ?? index"
                class="form-field"
                :class="{ 'field-selected': selectedFieldId === field.id }"
                @click="selectField(field)"
            >
                <div class="field-header">
                    <div class="field-drag-handle">
                        <i class="dx-icon-menu"></i>
                    </div>
                    <div class="field-title">{{ field.label || 'Unnamed Field' }}</div>
                    <div class="field-actions">
                        <button @click.stop="deleteField(field)" title="Delete field" class="field-action-button">
                            <i class="dx-icon-trash"></i>
                        </button>
                        <button @click.stop="duplicateField(field)" title="Duplicate field" class="field-action-button">
                            <i class="dx-icon-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="field-content">
                    <FormFieldRenderer :field="field" :is-preview="false" />
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="fields.length === 0" class="empty-state">
            <p>Ziehe Formularfelder hierher</p>
        </div>
    </div>
</template>

<style scoped>
.form-builder-canvas {
    background-color: #f9f9f9;
    border: 2px dashed #ccc;
    border-radius: 5px;
    min-height: 200px;
    padding: 15px;
    flex-grow: 1;
    overflow-y: auto;
}

.fields-list {
    min-height: 100px;
}

.form-field {
    background-color: white;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 10px;
    transition: all 0.2s;
}

.form-field:hover {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.field-selected {
    border-color: #1976d2;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.3);
}

.field-header {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    border-radius: 4px 4px 0 0;
}

.field-drag-handle {
    cursor: move;
    margin-right: 10px;
    color: #888;
}

.field-title {
    flex-grow: 1;
    font-weight: 500;
}

.field-actions {
    display: flex;
    gap: 5px;
}

.field-action-button {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 3px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.field-action-button:hover {
    background-color: #e0e0e0;
    color: #333;
}

.field-content {
    padding: 10px 15px;
}

.ghost {
    opacity: 0.5;
    background: #c8ebfb;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 150px;
    color: #999;
    font-style: italic;
}
</style>
