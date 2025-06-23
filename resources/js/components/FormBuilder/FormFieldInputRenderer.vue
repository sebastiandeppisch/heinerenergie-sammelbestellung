<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Input } from '@/shadcn/components/ui/input';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/shadcn/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Label } from '@/shadcn/components/ui/label';
import PinLocationMap from '../PinLocationMap.vue';

type FormFieldData = App.Data.FormFieldData;
type FieldType = App.Enums.FieldType;

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
    DATE: 'date' as FieldType,
    GEO_COORDINATE: 'geo_coordinate' as FieldType
};

const props = withDefaults(defineProps<{
  field: FormFieldData;
  disabled?: boolean;
  hasError?: boolean;
}>(), {
  disabled: false,
  hasError: false,
});

const modelValue = defineModel<any>();

const emit = defineEmits<{
  (e: 'change'): void;
  (e: 'input'): void;
}>();

const fieldOptions = computed(() => {
  if (props.field.options && props.field.options.length > 0) {
    return props.field.options.map(option => ({
      label: option.label,
      value: option.value,
      disabled: false
    }));
  }
  return [];
});

//just bubble up
function handleChange() {
    emit('change');
}
function handleInput() {
    emit('input');
}

function handleCheckboxChange(optionValue: string, checked: boolean | string) {
  const isChecked = typeof checked === 'boolean' ? checked : Boolean(checked);

  if (Array.isArray(modelValue.value)) {
    if (isChecked) {
      modelValue.value = [...modelValue.value, optionValue];
    } else {
      modelValue.value = modelValue.value.filter(v => v !== optionValue);
    }
  } else {
    modelValue.value = isChecked ? [optionValue] : [];
  }
  handleChange();
}

const fieldId = computed<string>(() => `field_${props.field.id}`);
const inputClasses = computed(() => ({
  'border-destructive': props.hasError
}));
</script>

<template>
  <Input
    v-if="field.type === FIELD_TYPES.TEXT"
    :id="fieldId"
    v-model="modelValue"
    type="text"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :maxlength="field.max_length"
    :class="inputClasses"
    @change="handleChange"
    @input="handleInput"
  />

  <Textarea
    v-else-if="field.type === FIELD_TYPES.TEXTAREA"
    :id="fieldId"
    v-model="modelValue"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :maxlength="field.max_length"
    :class="inputClasses"
    class="min-h-[100px]"
    @change="handleChange"
    @input="handleInput"
  />

  <Input
    v-else-if="field.type === FIELD_TYPES.NUMBER"
    :id="fieldId"
    v-model="modelValue"
    type="number"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :min="field.min_value"
    :max="field.max_value"
    :class="inputClasses"
    @change="handleChange"
    @input="handleInput"
  />

  <Input
    v-else-if="field.type === FIELD_TYPES.EMAIL"
    :id="fieldId"
    v-model="modelValue"
    type="email"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :class="inputClasses"
    @change="handleChange"
    @input="handleInput"
  />

  <Input
    v-else-if="field.type === FIELD_TYPES.PHONE"
    :id="fieldId"
    v-model="modelValue"
    type="tel"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :class="inputClasses"
    @change="handleChange"
    @input="handleInput"
  />

  <Select
    v-else-if="field.type === FIELD_TYPES.SELECT"
    v-model="modelValue"
    :disabled="disabled"
    @update:modelValue="handleChange"
  >
    <SelectTrigger :class="inputClasses">
      <SelectValue :placeholder="field.placeholder || 'Auswählen...'" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem
        v-for="option in fieldOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </SelectItem>
    </SelectContent>
  </Select>

  <RadioGroup
    v-else-if="field.type === FIELD_TYPES.RADIO"
    v-model="modelValue"
    :disabled="disabled"
    class="space-y-2"
    @update:modelValue="handleChange"
  >
    <div
      v-for="option in fieldOptions"
      :key="option.value"
      class="flex items-center space-x-2"
    >
      <RadioGroupItem :value="option.value" :id="`${fieldId}_${option.value}`" />
      <Label :for="`${fieldId}_${option.value}`" class="text-sm font-normal">
        {{ option.label }}
      </Label>
    </div>
  </RadioGroup>

  <div v-else-if="field.type === FIELD_TYPES.CHECKBOX" class="space-y-2">
    <div
      v-for="option in fieldOptions"
      :key="option.value"
      class="flex items-center space-x-2"
    >
      <Checkbox
        :id="`${fieldId}_${option.value}`"
        :checked="Array.isArray(modelValue) ? modelValue.includes(option.value) : modelValue === option.value"
        :disabled="disabled"
        @update:checked="(checked: boolean) => handleCheckboxChange(option.value, checked)"
      />
      <Label :for="`${fieldId}_${option.value}`" class="text-sm font-normal">
        {{ option.label }}
      </Label>
    </div>
  </div>

  <Input
    v-else-if="field.type === FIELD_TYPES.FILE"
    :id="fieldId"
    type="file"
    :disabled="disabled"
    :multiple="false"
    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
    :class="inputClasses"
    @change="handleChange"
  />

  <Input
    v-else-if="field.type === FIELD_TYPES.DATE"
    :id="fieldId"
    v-model="modelValue"
    type="date"
    :placeholder="field.placeholder"
    :disabled="disabled"
    :min="field.min_value"
    :max="field.max_value"
    :class="inputClasses"
    @change="handleChange"
    @input="handleInput"
  />
  <PinLocationMap
    v-else-if="field.type == FIELD_TYPES.GEO_COORDINATE"
    :id="fieldId"
    v-model="modelValue"
    />

  <div v-else class="p-2 border border-destructive rounded-md text-destructive text-sm">
    Feldtyp "{{ field.type }}" wird nicht unterstützt
  </div>
</template>
