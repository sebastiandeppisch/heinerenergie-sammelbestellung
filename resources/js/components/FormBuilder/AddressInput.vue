<template>
  <div class="space-y-4" v-if="props.readonly === false">
    <div class="grid grid-cols-4 gap-4">
      <div class="col-span-3">
        <Label for="street">Straße</Label>
        <Input
          id="street"
          v-model="model.street"
          type="text"
          placeholder="Musterstraße"
        />
      </div>
      <div class="col-span-1">
        <Label for="number">Nr.</Label>
        <Input
          id="number"
          v-model="model.streetNumber"
          type="text"
          placeholder="123"
        />
      </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
      <div class="col-span-1">
        <Label for="zipCode">PLZ</Label>
        <Input
          id="zipCode"
          v-model="intZip"
          type="text"
          placeholder="12345"
          pattern="[0-9]{5}"
        />
      </div>
      <div class="col-span-3">
        <Label for="city">Ort</Label>
        <Input
          id="city"
          v-model="model.city"
          type="text"
          placeholder="Musterstadt"
        />
      </div>
    </div>
  </div>
  <div v-else>
    {{ model.street }} {{ model.streetNumber }}<br> {{ intZip }} {{ model.city }}
  </div>
</template>

<script lang="ts" setup>
import Input from '@/shadcn/components/ui/input/Input.vue'
import Label from '@/shadcn/components/ui/label/Label.vue'
import { computed, onMounted } from 'vue';
type Address = App.ValueObjects.Address;


const props = withDefaults(defineProps<{
    readonly?: boolean;
}>(), {
    readonly: false
});

const model = defineModel<Address>({
    default: () =>({
        street: 'Standard Straße',
        streetNumber: '',
        zip: 0,
        city: ''
    }) as Address,
    required: true
});

const intZip = computed<string>({
    get() {

        if(model.value === undefined || model.value.zip === undefined){
            return '';
        }



        return model.value.zip.toString()
    },
    set(value) {
        model.value.zip = parseInt(value)
    }
});

onMounted(() => {
    if(model.value !== undefined && model.value.zip === undefined){
        model.value = {
            street: '',
            streetNumber: '',
            zip: 0,
            city: ''
        } as Address;
    }
})


</script>
