<script setup lang="ts">

import { DxAutocomplete } from 'devextreme-vue/autocomplete';
import DxTextArea from 'devextreme-vue/text-area';
import { DxLookup} from 'devextreme-vue/lookup';


import {DxForm, DxSimpleItem, DxGroupItem, DxButtonItem} from 'devextreme-vue/form';

import { DxAsyncRule } from 'devextreme-vue/form';

import axios, {AxiosError, AxiosResponse} from 'axios';
import {ref, reactive} from 'vue';

import dxForm from 'devextreme/ui/form';

import {notifyError} from '../helpers'
import notify from 'devextreme/ui/notify';
import { ValidationResult } from 'devextreme/ui/validation_group';
import LaravelLookupSource from '../LaravelLookupSource';

const emit = defineEmits(['update'])

interface Props {
  order: App.Models.Order
  confirmEmail: boolean,
  updateButton: boolean,
  allowEditing: boolean
}
const {order, confirmEmail = true, updateButton, allowEditing = true} = defineProps<Props>();

let citySuggestions = ref([]);

const form = ref(null);

function validateAsync(params){
  const data = {...order};
  const field: string = params.formItem.dataField;
  data[field] = params.value;
  let url = '';
  if('id' in order && order.id > 0){
    url = 'api/validateeditorderform';
  }else{
    url = 'api/validateorderform';
  }
  return cachedValidationRequest(url, data).catch(error => {
    formatError(error, field);
  })
}

const validationCache = new Map();

function cachedValidationRequest(url, data): Promise<AxiosResponse>{
  const key = url + JSON.stringify(data);
  if(! validationCache.has(key)){
    validationCache.set(key, axios.get(url, {
      params: data
    }))
  }
  return validationCache.get(key);
}


function loadUsers(): LaravelLookupSource{
  return new LaravelLookupSource('api/users');
}

function formatError(error: AxiosError, field: string): void{
  if(error.response && error.response.status === 422){
    for(const prop in error.response.data.errors){
      if(prop === field){
        const validationErrors  = error.response.data.errors[prop] as Array<String>;
        throw(validationErrors.join(","));
      }
    }
  }
}

const cityZips = [64283, 64285, 64287, 64293, 64295, 64289];
const subUrbZips = {
  64289: ['Darmstadt-Kranichstein'],
  64291: ['Darmstadt-Arheilgen', 'Darmstadt-Wixhausen'],
  64297: ['Darmstadt-Eberstadt']
}

function zipChanged(e){
  let suggestion = [];
  let zip = parseInt(order.zip + "");

  if(cityZips.includes(zip)){
    suggestion.push('Darmstadt');
  }
  if(zip in subUrbZips){
    suggestion = suggestion.concat(subUrbZips[zip])
  }
  if(suggestion.length === 1){
    order.city = suggestion[0];
  }
  citySuggestions.value.length = 0;
  suggestion.forEach((s) => {
    citySuggestions.value.push(s);
  })
}

const submitButtonOptions = {
  text: "Besteller*in-Daten Speichern",
  useSubmitBehavior: false,
  type: 'default',
  width: "100%",
  onClick: () => {
    const formInstance = form.value.instance as dxForm;
    formInstance.validate().complete.then(result => {
      if(result.isValid){
         axios.put('api/orders/' + order.id, {
            ...order
          }).then((response) => {
            notify('Daten wurden gespeichert', 'success');
            emit('update');
          }).catch(error => {
            notifyError(error)
          })
      }
    });
  }
}

function submit(){
  console.log("submit")
}

const validate: () => Promise<ValidationResult> = () => {
  const formInstance = form.value.instance as dxForm;
  return formInstance.validate().complete
}

defineExpose({
  validate
})

</script>

<template>
  <DxForm
    ref="form"
    label-mode="floating"
    :col-count="2"
    :form-data="order"
    @submit="submit"
    :disabled="! allowEditing"
  >
      <DxSimpleItem
        data-field="firstName"
        :label="{ text: 'Vorname'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="lastName"
        :label="{ text: 'Nachname'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="email_confirmation"
        :label="{ text: 'E-Mail'}"
        v-if="confirmEmail"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="email"
        :label="{ text: 'E-Mail Wiederholung'}"
        v-if="confirmEmail"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>

      <DxSimpleItem
        data-field="email"
        :label="{ text: 'E-Mail'}"
        v-if="!confirmEmail"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
    
      <DxSimpleItem
        data-field="phone"
        :label="{ text: 'Telefonnummer'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="street"
        :label="{ text: 'Straße'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="streetNumber"
        :label="{ text: 'Hausnummer'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="zip"
        :label="{ text: 'Postleitzahl'}"
        editor-type="dxAutocomplete"
        :editor-options="{items: ['64283', '64285', '64287', '64289', '64293', '64295',  '64291', '64297'], minSearchLength: 0, searchTimeout: 0, onChange: zipChanged, maxLength: 5}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="city"
        :label="{ text: 'Stadt'}"
        editor-type="dxAutocomplete"
        :editor-options="{
            items: citySuggestions,
            minSearchLength: 0,
            searchTimeout: 0
        }"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem v-if="confirmEmail"
        data-field="advisorEmail"
        :label="{ text: 'Berater*in E-Mail'}"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem v-else
        data-field="advisor_id"
        :label="{ text: 'Berater*in'}"
        editor-type="dxLookup"
        :editor-options="{
            dataSource: loadUsers(),
            displayExpr: 'name',
            valueExpr: 'id'
        }"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
      <DxSimpleItem
        data-field="commentary"
        :label="{ text: 'Kommentar'}"
        editor-type="dxTextArea"
        :editor-options="{
          autoResizeEnabled: true
        }"
      >
        <DxAsyncRule
          :validation-callback="validateAsync"
        />
      </DxSimpleItem>
    <DxButtonItem
      v-if="updateButton"
      :button-options="submitButtonOptions"
    />
  </DxForm>
  <div style="display:none;">
  <!-- otherwise vue will not auto import DxAutocomplete-->
  <DxAutocomplete />
  <DxTextArea />
  <DxLookup />

  </div>
</template>
