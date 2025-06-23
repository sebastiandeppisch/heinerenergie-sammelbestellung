<script setup lang="ts">
import FormFieldRenderer from './FormFieldRenderer.vue';

defineProps<{
    formSubmission: App.Data.FormSubmissionData;
}>();

</script>

<template>
    <div class="form-renderer">
        <div class="space-y-4">
            <FormFieldRenderer
                v-for="field in formSubmission.fields"
                :key="field.field.id"
                :field="field.field"
                :field-name="field.field.id"
                :disabled="true"
                v-model="field.value"
            />
        </div>
    </div>
</template>

<style scoped>
.form-renderer {
    max-width: 800px;
    width: 100%;
}
</style>
