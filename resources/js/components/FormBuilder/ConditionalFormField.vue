<script setup lang="ts">
import type { DependentFields } from '@/utils/formFieldVisibility';
import { computed } from 'vue';
import FormFieldRenderer from './FormFieldRenderer.vue';

type FormFieldData = App.Data.FormFieldData;

/**
 * A form field with the fields depending on its options. They are shown indented below the chosen option, or below
 * the field for a select.
 */
const props = withDefaults(
    defineProps<{
        field: FormFieldData;
        values: Record<string, any>;
        dependents: DependentFields;
        errors?: Record<string, string | Array<string> | undefined>;
        disabled?: boolean;
    }>(),
    {
        errors: () => ({}),
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'update', fieldId: string, value: unknown): void;
    (e: 'validate', fieldId: string): void;
}>();

const dependentsByOption = computed(() => props.dependents.get(props.field.id));

function isChosen(optionValue: string): boolean {
    const value = props.values[props.field.id];

    return Array.isArray(value) ? value.includes(optionValue) : value === optionValue;
}

/** The fields shown for the chosen option of a select, which has no place below each option. */
const selectDependents = computed(() =>
    props.field.type === 'select' && typeof props.values[props.field.id] === 'string'
        ? (dependentsByOption.value?.get(props.values[props.field.id]) ?? [])
        : [],
);
</script>

<template>
    <FormFieldRenderer
        :field="field"
        :field-name="field.id"
        :errors="errors[field.id] ?? []"
        :disabled="disabled"
        :model-value="values[field.id]"
        @update:model-value="(value) => emit('update', field.id, value)"
        @validate="emit('validate', field.id)"
    >
        <template v-if="dependentsByOption && field.type !== 'select'" #after-option="{ optionValue }">
            <div
                v-if="isChosen(optionValue) && (dependentsByOption.get(optionValue) ?? []).length > 0"
                class="mt-2 mb-3 ml-2 space-y-4 border-l-2 pl-4"
                data-test="dependent-fields"
            >
                <ConditionalFormField
                    v-for="dependent in dependentsByOption.get(optionValue)"
                    :key="dependent.id"
                    :field="dependent"
                    :values="values"
                    :dependents="dependents"
                    :errors="errors"
                    :disabled="disabled"
                    @update="(fieldId, value) => emit('update', fieldId, value)"
                    @validate="(fieldId) => emit('validate', fieldId)"
                />
            </div>
        </template>
        <template v-if="selectDependents.length > 0" #after-input>
            <div class="mt-2 ml-2 space-y-4 border-l-2 pl-4" data-test="dependent-fields">
                <ConditionalFormField
                    v-for="dependent in selectDependents"
                    :key="dependent.id"
                    :field="dependent"
                    :values="values"
                    :dependents="dependents"
                    :errors="errors"
                    :disabled="disabled"
                    @update="(fieldId, value) => emit('update', fieldId, value)"
                    @validate="(fieldId) => emit('validate', fieldId)"
                />
            </div>
        </template>
    </FormFieldRenderer>
</template>
