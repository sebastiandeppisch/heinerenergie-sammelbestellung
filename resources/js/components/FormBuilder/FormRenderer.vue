<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { dependentFieldsByOption, topLevelFields, withoutHiddenValues } from '@/utils/formFieldVisibility';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConditionalFormField from './ConditionalFormField.vue';

type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    formDefinition: FormDefinitionData;
    submitUrl: string;
    method?: 'post' | 'put' | 'patch';
    initialData?: Record<string, any>;
    formToken?: string;
}>();

const emit = defineEmits<{
    (e: 'submit', data: any): void;
    (e: 'success', response: any): void;
    (e: 'error', error: any): void;
}>();

const createInitialFormData = () => {
    const data: Record<string, any> = {};

    props.formDefinition.fields.forEach((field) => {
        if (field.options && field.options.length > 0) {
            const defaultOption = field.options.find((option) => option.is_default);
            if (defaultOption) {
                data[field.id] = defaultOption.value;
            } else {
                data[field.id] = null;
            }
        } else {
            data[field.id] = field.default_value || '';
        }

        if (field.type === 'checkbox' || field.type === 'image') {
            data[field.id] = [];
        }
    });

    if (props.initialData) {
        Object.assign(data, props.initialData);
    }

    data._form_token = props.formToken || '';

    return data;
};

const form = useForm(props.method || 'post', props.submitUrl, createInitialFormData());
form.setValidationTimeout(100);
function validateField(fieldName: string) {
    form.validate(fieldName);
}

function submitForm() {
    emit('submit', form.data());
    form.transform((data) => withoutHiddenValues(props.formDefinition.fields, data)).submit({
        onSuccess: (page) => emit('success', page),
        onError: (errors) => emit('error', errors),
    });
}

const visibleFields = computed(() => topLevelFields(props.formDefinition.fields));
const dependents = computed(() => dependentFieldsByOption(props.formDefinition.fields));

function updateValue(fieldId: string, value: unknown) {
    form[fieldId] = value;
}
</script>

<template>
    <div class="form-renderer">
        <form @submit.prevent="submitForm">
            <div v-if="formDefinition.name" class="mb-6">
                <h2 class="text-2xl font-bold">{{ formDefinition.name }}</h2>
                <p v-if="formDefinition.description" class="mt-2 text-muted-foreground">
                    {{ formDefinition.description }}
                </p>
            </div>

            <div class="space-y-4">
                <ConditionalFormField
                    v-for="field in visibleFields"
                    :key="field.id"
                    :field="field"
                    :values="form.data()"
                    :dependents="dependents"
                    :errors="form.errors"
                    :disabled="form.processing"
                    @update="updateValue"
                    @validate="validateField"
                />
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6">
                <Button type="submit" :disabled="form.processing" class="min-w-[120px]">
                    <span v-if="form.processing">Wird verarbeitet...</span>
                    <span v-else>Absenden</span>
                </Button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.form-renderer {
    max-width: 800px;
    width: 100%;
}
</style>
