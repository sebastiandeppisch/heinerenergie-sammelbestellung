<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Label } from '@/shadcn/components/ui/label';
import FormFieldInputRenderer from './FormFieldInputRenderer.vue';

type FormFieldData = App.Data.FormFieldData;

const props = withDefaults(defineProps<{
  field: FormFieldData;
  isPreview?: boolean;
  errors: Array<string>|string;
  fieldName?: string;
  disabled?: boolean;
}>(), {
  isPreview: true,
  errors: () => [],
  fieldName: '',
  disabled: false
});

const modelValue = defineModel<any>();

const emit = defineEmits<{
  (e: 'validate', fieldName: string): void;
}>();

/*
    track if the field has had an error
    validate on input if it has had an error
    validate on change if it has not had an error
    for better UX
*/
const hasHadError = ref(false);


watch(() => props.errors, (newError) => {
  if (newError.length > 0) {
    hasHadError.value = true;
  }
});


function handleValidation() {
  if (props.fieldName && props.isPreview) {
    emit('validate', props.fieldName);
  }
}

function handleChange() {
  handleValidation();
}

function handleInput() {
  if (hasHadError.value) {
    handleValidation();
  }
}

const hasError = computed(() => props.errors.length > 0);
const fieldDisabled = computed(() => props.disabled || !props.isPreview);

const fieldId = computed<string>(() => `field_${props.field.id}`);
</script>

<template>
  <div class="field-renderer space-y-2">
    <Label :for="fieldId" class="text-sm font-medium">
      {{ field.label }}
      <span v-if="field.required" class="text-destructive ml-1">*</span>
    </Label>

    <div v-if="field.help_text" class="text-xs text-muted-foreground">
      {{ field.help_text }}
    </div>

    <FormFieldInputRenderer
      :field="field"
      :disabled="fieldDisabled"
      :has-error="hasError"
      :has-had-error="hasHadError"
      v-model="modelValue"
      @change="handleChange"
      @input="handleInput"
    />

    <div v-if="hasError" class="text-destructive text-xs mt-1">
      <div v-if="typeof errors === 'string'">
        {{ errors }}
      </div>
      <div v-else>
        <div v-for="(error, index) in errors" :key="index">
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.field-renderer {
  width: 100%;
}
</style>
