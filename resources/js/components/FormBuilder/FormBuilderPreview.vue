<script setup lang="ts">
import notify from 'devextreme/ui/notify';
import { ref } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { RotateCcw, Send } from 'lucide-vue-next';
import FormFieldRenderer from './FormFieldRenderer.vue';

type FormDefinitionData = App.Data.FormDefinitionData;

const props = defineProps<{
    formDefinition: FormDefinitionData;
}>();

const formValues = ref<Record<string, any>>({});

function resetForm() {
    formValues.value = {};
}

function submitForm() {
    const missingRequiredFields = props.formDefinition.fields.filter((field) => field.required && !formValues.value[field.id]);

    if (missingRequiredFields.length > 0) {
        notify('Bitte füllen alle Pflichtfelder aus', 'error', 3000);
        return;
    }

    notify('Formular erfolgreich gesendet', 'success', 2000);
    console.log('Formulardaten:', formValues.value);

    setTimeout(() => {
        resetForm();
    }, 1000);
}
</script>

<template>
    <div class="form-preview">
        <h3 class="preview-title">{{ formDefinition.name }}</h3>
        <p v-if="formDefinition.description" class="preview-description">
            {{ formDefinition.description }}
        </p>

        <div class="fields-container">
            <div v-for="(field, index) in formDefinition.fields" :key="field.id ?? index" class="field-wrapper">
                <FormFieldRenderer :field="field" :is-preview="true" :field-name="field.id" :errors="[]" v-model="formValues[field.id]" />
            </div>
        </div>

        <div class="form-actions">
            <Button variant="outline" @click="resetForm">
                <RotateCcw class="h-4 w-4" />
                Zurücksetzen
            </Button>
            <Button variant="default" @click="submitForm">
                <Send class="h-4 w-4" />
                Absenden
            </Button>
        </div>
    </div>
</template>

<style scoped>
.form-preview {
    background-color: #fff;
    border-radius: 6px;
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.preview-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

.preview-description {
    margin-bottom: 20px;
    color: #555;
}

.fields-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-bottom: 30px;
}

.field-wrapper {
    width: 100%;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
</style>
