<template>
  <div style="display: flex;flex-direction: column;height:100%;gap:32px;">
      <span style="font-size:1.2em">Um Dich zu kontaktieren, brauchen wir zuerst Deine Kontaktdaten</span>


      <div style="display:flex;flex-direction:row;gap:16px;">
        <DxTextBox
          v-model="advice.firstName"
          placeholder="Erika"
          label="Vorname"
          @change="checkForm"
          @key-up="checkForm"
          value-change-event="keyup"
          validation-message-mode="always"
          width="100%"
          >
          <DxValidator @validated="contactValidated" name="firstName">
            <DxRequiredRule message="Wir benötigen Deinen Vornamen"/>
          </DxValidator> 
        </DxTextBox>
        <DxTextBox
          v-model="advice.lastName"
          placeholder="Musterfrau"
          validation-message-mode="always"
          label="Nachname"
          @change="checkForm"
          @key-up="checkForm"
          value-change-event="keyup"
          width="100%"
          >
          <DxValidator @validated="contactValidated" name="lastName">
            <DxRequiredRule message="Wir benötigen Deinen Nachnamen"/>
          </DxValidator> 
        </DxTextBox>
      </div>
      
      <DxTextBox
        v-model="advice.email"
        placeholder="max@mustermann.de"
        mode="email"
        label="E-Mail"
        validation-message-mode="always"
        @change="change"
        @key-up="checkForm"
        :value-change-event="valueChangeEvent"
      >
        <DxValidator  @validated="contactValidated" name="email">
          <DxRequiredRule message="Ohne E-Mail-Adresse können wir Dich nicht kontaktieren"/>
          <DxEmailRule message="Die eingegebene E-Mail Adresse ist ungültig"/>
        </DxValidator>
      </DxTextBox>
      <DxTextBox
        v-model="advice.phone"
        placeholder="06151/12345"
        mode="tel"
        label="Telefon"
        validation-message-mode="always"
        @change="change"
        @key-up="checkForm"
        :value-change-event="valueChangeEvent"
      >
        <DxValidator @validated="contactValidated" name="phone">
          <DxRequiredRule message="Ohne Telefonnummer können wir Dich nicht kontaktieren"/>
        </DxValidator> 
      </DxTextBox>
      <div style="flex-grow: 1"></div>
  </div>
</template>

<script setup lang="ts">
import DxTextBox from "devextreme-vue/text-box";

import { computed, reactive, ref } from "vue";

import {
  DxValidator,
  DxRequiredRule,
  DxEmailRule
} from 'devextreme-vue/validator';

interface Props {
  modelValue: App.Models.Advice
}

const props = defineProps<Props>();
const emit = defineEmits(["allowForward"])
const valueChangeEvent = ref('change');
const notTypedYet = ref(true);

const r = reactive({
  mailValid: false,
  phoneValid: false,
  firstNameValid: false,
  lastNameValid: false
})

function contactValidated(e){
  if(e.name === 'email'){
    r.mailValid = e.isValid;
  }

  if(e.name === 'phone'){
    r.phoneValid = e.isValid;
  }

  if(e.name === 'firstName'){
    r.firstNameValid = e.isValid;
  }
  
  if(e.name === 'lastName'){
    r.lastNameValid = e.isValid;
  }
}

function checkForm(){
  notTypedYet.value = false;
  if(r.mailValid === false || r.phoneValid === false || advice.value.email === '' || advice.value.phone === '' || r.firstNameValid === false || r.lastNameValid === false || advice.value.firstName === '' || advice.value.lastName === ''){
    emit('allowForward', false)
  }else{
    emit('allowForward', true)
  }
}

function change(){
  valueChangeEvent.value = 'keyup';
  checkForm();
}

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue
  },
  set(value) {
  }
})

</script>
