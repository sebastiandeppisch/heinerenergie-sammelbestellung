<script setup lang="ts">
import { ref, watch, reactive, computed } from 'vue';
import { nanoid } from 'nanoid';
import { v4 as uuidv4 } from 'uuid';
import { Label } from '@/shadcn/components/ui/label';
import { Input } from '@/shadcn/components/ui/input';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Button } from '@/shadcn/components/ui/button';
import { PlusIcon, TrashIcon } from 'lucide-vue-next';

type FormFieldData = App.Data.FormFieldData;
type FieldType = App.Enums.FieldType;
type FormFieldOptionData = App.Data.FormFieldOptionData;

const FIELD_TYPES = {
  TEXT: 'text' as FieldType,
  TEXTAREA: 'textarea' as FieldType,
  NUMBER: 'number' as FieldType,
  EMAIL: 'email' as FieldType,
  PHONE: 'phone' as FieldType,
  SELECT: 'select' as FieldType,
  RADIO: 'radio' as FieldType,
  CHECKBOX: 'checkbox' as FieldType,
  FILE: 'file' as FieldType,
  DATE: 'date' as FieldType
};



const model = defineModel<FormFieldData>({
    required: true
});


//TODO export arrays from backend

const supportsOptions = computed(() => {
  return [FIELD_TYPES.SELECT, FIELD_TYPES.RADIO, FIELD_TYPES.CHECKBOX].includes(model.value.type);
});

const supportsLengthValidation = computed(() => {
  return [FIELD_TYPES.TEXT, FIELD_TYPES.TEXTAREA].includes(model.value.type);
});

const supportsValueValidation = computed(() => {
  return [FIELD_TYPES.NUMBER, FIELD_TYPES.DATE].includes(model.value.type);
});

const supportsFileTypes = computed(() => {
  return model.value.type === FIELD_TYPES.FILE;
});

const placeholder = computed({
  get: () => model.value.placeholder || '',
  set: (value: string) => {
    model.value.placeholder = value || null;
  }
});

const helpText = computed({
  get: () => model.value.help_text || '',
  set: (value: string) => {
    model.value.help_text = value || null;
  }
});

const defaultValue = computed({
  get: () => model.value.default_value || '',
  set: (value: string) => {
    model.value.default_value = value || null;
  }
});

const minLength = computed({
  get: () => model.value.min_length?.toString() || '',
  set: (value: string) => {
    model.value.min_length = value ? parseInt(value) : null;
  }
});

const maxLength = computed({
  get: () => model.value.max_length?.toString() || '',
  set: (value: string) => {
    model.value.max_length = value ? parseInt(value) : null;
  }
});

const minValue = computed({
  get: () => model.value.min_value?.toString() || '',
  set: (value: string) => {
    model.value.min_value = value ? parseFloat(value) : null;
  }
});

const maxValue = computed({
  get: () => model.value.max_value?.toString() || '',
  set: (value: string) => {
    model.value.max_value = value ? parseFloat(value) : null;
  }
});

function addOption() {
  const newOption: FormFieldOptionData = {
    label: 'Neue Option',
    value: `option_${nanoid(6)}`,
    sort_order: model.value.options.length,
    is_default: false,
    id: uuidv4(),
  };

  model.value.options.push(newOption as FormFieldOptionData);
  updateOptions();
}

function removeOption(option: FormFieldOptionData) {
  model.value.options = model.value.options.filter(opt => opt !== option);
  updateOptions();
}

function updateOptions() {
  model.value.options = [...model.value.options];

  model.value.options.forEach((option, index) => {
    option.sort_order = index;
  });
}

function updateAcceptedFileTypes(types: string) {
  if (model.value.type === FIELD_TYPES.FILE) {
    model.value.accepted_file_types = types.split(',').map(t => t.trim());
  }
}

function updateField() {
}

function onValueChanged(e: any) {
  updateField();
}

</script>

<template>
  <div class="properties-panel">
    <h3 class="panel-title">Feldeigenschaften</h3>

    <div class="panel-content p-1">
      <form @submit.prevent="updateField">
        <div class="form-section">
          <h4 class="section-title">Allgemein</h4>

          <div class="grid gap-2">
            <Label for="field_label">Feldbezeichnung</Label>
            <Input
              id="field_label"
              v-model="model.label"
              placeholder="Feldbezeichnung"
            />
          </div>

          <div class="grid gap-2 mt-4">
            <div class="flex items-center space-x-2">
              <Checkbox
                id="field_required"
                v-model="model.required"
              />
              <Label for="field_required">Pflichtfeld</Label>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h4 class="section-title">Anzeige</h4>

          <div class="grid gap-2">
            <Label for="field_placeholder">Platzhaltertext</Label>
            <Input
              id="field_placeholder"
              v-model="placeholder"
              placeholder="Platzhaltertext"
              @input="onValueChanged"
            />
          </div>

          <div class="grid gap-2 mt-4">
            <Label for="field_help_text">Hilfetext</Label>
            <Textarea
              id="field_help_text"
              v-model="helpText"
              placeholder="Hilfetext"
              class="min-h-[60px]"
              @input="onValueChanged"
            />
          </div>

          <div class="grid gap-2 mt-4">
            <Label for="field_default_value">Standardwert</Label>
            <Input
              id="field_default_value"
              v-model="defaultValue"
              placeholder="Standardwert"
              @input="onValueChanged"
            />
          </div>
        </div>

        <div v-if="supportsLengthValidation" class="form-section">
          <h4 class="section-title">Textvalidierung</h4>

          <div class="grid gap-2">
            <Label for="field_min_length">Minimale Länge</Label>
            <Input
              id="field_min_length"
              v-model="minLength"
              type="number"
              placeholder="Minimale Länge"
              min="0"
              @input="onValueChanged"
            />
          </div>

          <div class="grid gap-2 mt-4">
            <Label for="field_max_length">Maximale Länge</Label>
            <Input
              id="field_max_length"
              v-model="maxLength"
              type="number"
              placeholder="Maximale Länge"
              min="0"
              @input="onValueChanged"
            />
          </div>
        </div>

        <div v-if="supportsValueValidation" class="form-section">
          <h4 class="section-title">Wertebereich</h4>

          <div class="grid gap-2">
            <Label for="field_min_value">Minimalwert</Label>
            <Input
              id="field_min_value"
              v-model="minValue"
              type="number"
              placeholder="Minimalwert"
              @input="onValueChanged"
            />
          </div>

          <div class="grid gap-2 mt-4">
            <Label for="field_max_value">Maximalwert</Label>
            <Input
              id="field_max_value"
              v-model="maxValue"
              type="number"
              placeholder="Maximalwert"
              @input="onValueChanged"
            />
          </div>
        </div>

        <div v-if="supportsFileTypes" class="form-section">
          <h4 class="section-title">Dateitypen</h4>

          <div class="grid gap-2">
            <Label for="field_file_types">Akzeptierte Dateitypen</Label>
            <Input
              id="field_file_types"
              type="text"
              placeholder="z.B. .jpg,.png,.pdf"
              :value="model.accepted_file_types?.join(', ')"
              @input="updateAcceptedFileTypes(($event.target as HTMLInputElement).value)"
            />
            <div class="text-hint">
              Kommagetrennte Liste von Dateierweiterungen
            </div>
          </div>
        </div>
      </form>

      <div v-if="supportsOptions" class="options-section">
        <h4 class="section-title">Auswahloptionen</h4>
        <div class="options-grid">
          <div v-for="option in model.options" :key="option.id" class="option-row">
            <div class="option-inputs">
              <Input
                v-model="option.label"
                placeholder="Bezeichnung"
                class="option-label"
                @change="updateOptions"
              />
              <label class="option-default">
                <input
                  type="radio"
                  name="default-option"
                  :checked="option.is_default"
                  @change="() => {
                    model.options.forEach(o => o.is_default = false);
                    option.is_default = true;
                    updateOptions();
                  }"
                >
                Standard
              </label>
            </div>
            <Button
              variant="ghost"
              size="sm"
              @click="removeOption(option)"
            >
              <TrashIcon />
            </Button>
          </div>

          <Button
            variant="outline"
            class="add-option-btn"
            @click="addOption"
          >
          <PlusIcon /> Option hinzufügen
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.properties-panel {
  background-color: #f8fafc;
  border-radius: 8px;
  padding: 16px;
  height: 100%;
  border: 1px solid #e2e8f0;
}

.panel-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #1e293b;
}

.panel-content {
  /*overflow-y: auto;
  max-height: 700px;*/
  min-width: 350px;
}

.form-section {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
  border-bottom: none;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #475569;
}

.options-section {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.options-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.option-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background-color: #f1f5f9;
  border-radius: 6px;
}

.option-inputs {
  display: flex;
  align-items: center;
  flex-grow: 1;
  gap: 12px;
}

.option-label {
  flex-grow: 1;
}

.option-default {
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
}

.option-default input[type="radio"] {
  width: 16px;
  height: 16px;
  accent-color: #3b82f6;
}

.add-option-btn {
  margin-top: 12px;
}

.text-hint {
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
}
</style>
