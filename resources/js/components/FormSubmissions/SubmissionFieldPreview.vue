<script setup lang="ts">
import { computed } from 'vue';
import AddressFieldPreview from './FieldPreview/AddressFieldPreview.vue';
import CoordinateFieldPreview from './FieldPreview/CoordinateFieldPreview.vue';

const props = defineProps<{
    submissionField: App.Data.SubmissionFieldData;
}>();

type Address = App.ValueObjects.Address;
type Coordinate = App.ValueObjects.Coordinate;

const value = computed(() => props.submissionField.value);
const fieldType = computed(() => props.submissionField.field.type);

const isObject = (v: unknown): v is Record<string, unknown> => v !== null && typeof v === 'object' && !Array.isArray(v);

const isAddress = computed(() => fieldType.value === 'address' && isObject(value.value));
const isCoordinate = computed(() => fieldType.value === 'geo_coordinate' && isObject(value.value));

const addressValue = computed((): Address => {
    if (!isObject(value.value)) throw new Error(`Expected address object, got ${typeof value.value}`);
    return value.value as unknown as Address;
});

const coordinateValue = computed((): Coordinate => {
    if (!isObject(value.value)) throw new Error(`Expected coordinate object, got ${typeof value.value}`);
    return value.value as unknown as Coordinate;
});

function formatValue(val: unknown): string {
    if (val === null || val === undefined) return '';
    if (Array.isArray(val)) return val.join(', ');
    if (typeof val === 'object') return JSON.stringify(val);
    return String(val);
}

const displayText = computed(() => formatValue(value.value));
</script>

<template>
    <span class="inline-flex items-center gap-1">
        <span class="text-gray-500">{{ submissionField.field.label }}:</span>
        <AddressFieldPreview v-if="isAddress" :value="addressValue" />
        <CoordinateFieldPreview v-else-if="isCoordinate" :value="coordinateValue" />
        <span v-else>{{ displayText }}</span>
    </span>
</template>
