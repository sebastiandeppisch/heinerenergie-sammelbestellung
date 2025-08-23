<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { useForm } from 'laravel-precognition-vue-inertia';
import { computed } from 'vue';
import FormFieldRenderer from './FormFieldRenderer.vue';

type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    formDefinition: FormDefinitionData;
    submitUrl: string;
    method?: 'post' | 'put' | 'patch';
    initialData?: Record<string, any>;
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
            }
        } else {
            data[field.id] = field.default_value || '';
        }

        if (field.type === 'checkbox') {
            data[field.id] = [];
        }
    });

    if (props.initialData) {
        Object.assign(data, props.initialData);
    }

    return data;
};

const form = useForm(props.method || 'post', props.submitUrl, createInitialFormData());
form.setValidationTimeout(100);
function validateField(fieldName: string) {
    form.validate(fieldName);
}

async function submitForm() {
    try {
        emit('submit', form.data());
        const response = await form.submit();
        emit('success', response);
    } catch (error) {
        emit('error', error);
    }
}

const visibleFields = computed(() => props.formDefinition.fields);
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
                <FormFieldRenderer
                    v-for="field in visibleFields"
                    :key="field.id"
                    :field="field"
                    :field-name="field.id"
                    :errors="form.errors[field.id] || []"
                    :disabled="form.processing"
                    v-model="form[field.id]"
                    @validate="validateField(field.id)"
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
