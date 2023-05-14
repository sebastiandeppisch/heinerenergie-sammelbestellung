<template>
  <div style="display: flex;flex-direction: column;height:100%">
      <span style="font-size:1.2em">Zuerst brauchen wir Deinen Namen</span>
      <div style="flex-grow: 1"></div>
      <DxTextBox
        v-model="advice.firstName"
        placeholder="Erika"
        label="Vorname"
        :is-valid="advice.firstName !== '' || notTypedYet"
        @change="checkForm"
        @key-up="checkForm"
        value-change-event="keyup"
      />
      <div style="flex-grow: 1"></div>
      <DxTextBox
        v-model="advice.lastName"
        placeholder="Musterfrau"
        label="Nachname"
        :is-valid="advice.lastName !== '' || notTypedYet"
        @change="checkForm"
        @key-up="checkForm"
        value-change-event="keyup"
      />
      <div style="flex-grow: 1"></div>
  </div>
</template>

<script setup lang="ts">
import DxTextBox from "devextreme-vue/text-box";
import { ref, computed } from "vue";


interface Props {
  modelValue: App.Models.Advice
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'allowForward', allow: boolean): void
}>()

const notTypedYet = ref(true);

function checkForm(){
  notTypedYet.value = false;
  if(advice.value.firstName !== '' && advice.value.lastName !== ''){
    emit('allowForward', true)
  }else{
    emit('allowForward', false)
  }
}

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue
  },
  set(value) {
    checkForm()
  }
})

</script>
