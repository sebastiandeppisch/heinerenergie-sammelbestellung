<template>
  <div>
    <h2 class="content-block">Neue Bestellung</h2>
    <DxForm
      id="form"
      label-mode="floating"
      :col-count="2"
      :form-data="formData"
    >
      <DxGroupItem
        caption="Persönliche Daten"
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
        >
            <DxAsyncRule
                :validation-callback="validateAsync"
              />
        </DxSimpleItem>
        <DxSimpleItem
          data-field="email"
          :label="{ text: 'E-Mail Wiederholung'}"
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
      </DxGroupItem>

      <DxGroupItem
        caption="Adresse"
      >
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
           :editor-options="{items: ['64283', '64285', '64287', '64289', '64293', '64295',  '64291', '64297'], minSearchLength: 0, searchTimeout: 0, onChange: zipChanged}"
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
      </DxGroupItem>

    </DxForm>

  <div style="display:none;">
    <!-- otherwise vue will not auto import DxAutocomplete-->
    <DxAutocomplete />
  </div>

  </div>
</template>

<script setup lang="ts">

import { DxAutocomplete } from 'devextreme-vue/autocomplete';

import {DxForm, DxSimpleItem, DxGroupItem,} from 'devextreme-vue/form';

import { DxRequiredRule, DxEmailRule, DxAsyncRule } from 'devextreme-vue/form';

import axios, {AxiosError} from 'axios';
import { ValidationCallbackData } from 'devextreme/ui/validation_rules';
import {ref} from 'vue';

let formData: any = ref({});

let citySuggestions = ref([]);

function validateAsync(params: ValidationCallbackData){
  const data = {...formData};
  const field: string = params.formItem.dataField;
  data[field] = params.value;
  console.log(data);
  return axios.get('api/validateorderform', {
    params: data
  }).catch(error => {
    formatError(error, field);
  })
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
  let zip = parseInt(formData.value.zip);
  console.log(formData.value.zip);

  if(cityZips.includes(zip)){
    suggestion.push('Darmstadt');
  }
  if(zip in subUrbZips){
    suggestion = suggestion.concat(subUrbZips[zip])
  }
  if(suggestion.length === 1){
    formData.value.city = suggestion[0];
  }
  citySuggestions.value = suggestion;
}

</script>
