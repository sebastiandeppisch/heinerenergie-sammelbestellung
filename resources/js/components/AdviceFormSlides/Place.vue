<template>
<div style="display: flex;flex-direction: column;height:100%">
  <span style="font-size:1.2em">Zu/An welchem Ort möchtest Du beraten werden?</span>
  <div>
    <div style="display:flex;">
    <DxTextBox
      placeholder="Luisenstraße"
      width="100%"
      :element-attr="{style: 'margin-right: 16px'}"
      label="Straße"
      v-model="advice.street"
      @change="checkForm"
      @key-up="checkForm"
      value-change-event="keyup"
    />
    <DxTextBox
      placeholder="42a"
      width="100px"
      label="Nr."
      v-model="advice.streetNumber"
      @change="checkForm"
      @key-up="checkForm"
      value-change-event="keyup"
    />
    </div>
    <div  style="display:flex;margin-top:16px;">
    <DxTextBox
      placeholder="64283"
      width="150px"
      label="Plz"
      v-model="advice.zip"
      @change="checkForm"
      @key-up="checkForm"
      value-change-event="keyup"
      mask="00000"
      mask-char=""
      mask-invalid-message="Gib bitte eine 5 stellige Postleitzahl an"
    >
      <DxValidator >
        <DxRequiredRule message="Gib bitte Deine Postleitzahl an"/>
        <DxStringLengthRule message="Gib bitte eine 5 stellige Postleitzahl an" :min="5" :max="5" />
      </DxValidator>
    </DxTextBox>
    <DxTextBox
      placeholder="Darmstadt"
      :element-attr="{style: 'margin-left: 16px'}"
      label="Ort"
      width="100%"
      v-model="advice.city"
      @change="checkForm"
      @key-up="checkForm"
      value-change-event="keyup"
    />
    </div>
  </div>

</div>
</template>

<script setup lang="ts">
import DxTextBox from "devextreme-vue/text-box";

import { computed } from "vue";

import {
  DxValidator,
  DxRequiredRule,
  DxEmailRule,
  DxStringLengthRule
} from 'devextreme-vue/validator';

interface Props {
  modelValue: App.Models.Advice
}

const props = defineProps<Props>();
const emit = defineEmits(["allowForward"])

function checkForm(){
  if(advice.value.street === '' || advice.value.streetNumber === '' || advice.value.zip === null || advice.value.zip.toString() === '' || advice.value.city === ''){
    emit('allowForward', false)
  }else{
    emit('allowForward', true)
  }
}

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue
  },
  set(value) {
  }
})

</script>
