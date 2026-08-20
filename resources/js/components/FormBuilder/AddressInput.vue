<template>
    <div class="space-y-4" v-if="props.readonly === false && model">
        <div class="grid grid-cols-4 gap-4">
            <div class="col-span-3">
                <Label for="street">Straße</Label>
                <Input id="street" v-model="model.street" :disabled="props.disabled" type="text" placeholder="Musterstraße" />
            </div>
            <div class="col-span-1">
                <Label for="number">Nr.</Label>
                <Input id="number" v-model="model.street_number" :disabled="props.disabled" type="text" placeholder="123" />
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="col-span-1">
                <Label for="zipCode">PLZ</Label>
                <Input id="zipCode" v-model="model.zip" :disabled="props.disabled" type="text" placeholder="12345" pattern="[0-9]{5}" />
            </div>
            <div class="col-span-3">
                <Label for="city">Ort</Label>
                <Input id="city" v-model="model.city" :disabled="props.disabled" type="text" placeholder="Musterstadt" />
            </div>
        </div>
    </div>
    <div v-else-if="props.readonly && hasAddress && model">
        {{ model.street }} {{ model.street_number }}<br />
        {{ model.zip }} {{ model.city }}
    </div>
    <em v-else-if="props.readonly" class="text-muted-foreground">Keine Adresse angegeben</em>
    <div v-else></div>
</template>

<script lang="ts" setup>
import Input from '@/shadcn/components/ui/input/Input.vue';
import Label from '@/shadcn/components/ui/label/Label.vue';
import { computed, onMounted } from 'vue';
type Address = App.ValueObjects.Address;

const emptyAddress = (): Address =>
    ({
        street: '',
        street_number: '',
        zip: '',
        city: '',
    }) as Address;

const props = withDefaults(
    defineProps<{
        readonly?: boolean;
        disabled?: boolean;
    }>(),
    {
        readonly: false,
        disabled: false,
    },
);

const model = defineModel<Address | null>({
    default: () =>
        ({
            street: '',
            street_number: '',
            zip: '',
            city: '',
        }) as Address,
    required: true,
});

/**
 * An address that was never filled in is stored as null and gets a hint instead of a blank line.
 * An address object with empty parts is a real, if empty, value — for example the form builder preview — and renders as such.
 */
const hasAddress = computed(() => typeof model.value === 'object' && model.value !== null);

onMounted(() => {
    if (props.readonly) {
        return;
    }

    if (!model.value || model.value.zip === undefined) {
        model.value = emptyAddress();
    }
});
</script>
