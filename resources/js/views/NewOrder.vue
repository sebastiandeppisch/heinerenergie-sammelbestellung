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


  <DxDataGrid
    :data-source="orderItemsDatasource"
  >
    <DxEditing
      :allow-updating="true"
      :allow-adding="false"
      :allow-deleting="false"
      mode="cell"
    />
    <DxColumn
      caption="Produkt"
      data-field="product.name"
      :allow-sorting="false"
      :allow-editing="false"
    />
    <DxColumn
      caption="Preis"
      data-field="product.price"
      :customize-text="formatPrice"
      :allow-sorting="false"
      :allow-editing="false"

    />
    <DxColumn
      caption="Anzahl"
      data-field="quantity"
      :allow-sorting="false"
      data-type="number"
      :editor-options="{defaultValue: 0, min: 0, showSpinButtons: true, showClearButton: true}"
    />
    <DxSummary
      :recalculate-while-editing="true"
      :calculate-custom-summary="calculateSummary"
      >
        <DxTotalItem
          name="priceSum"
          summary-type="custom"
          display-format="Gesamtpreis: {0}"
          show-in-column="quantity"
        />
    </DxSummary>
  </DxDataGrid>

<br><br>

  <DxButton 
    text="Bestellung absenden"
    type="default"
    width="100%"
    @click="saveOrder"
  />

  <div style="display:none;">
    <!-- otherwise vue will not auto import DxAutocomplete-->
    <DxAutocomplete />
  </div>

  </div>
</template>

<script setup lang="ts">

import DxButton from 'devextreme-vue/button';

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxSummary,
  DxTotalItem
} from "devextreme-vue/data-grid";

import { DxAutocomplete } from 'devextreme-vue/autocomplete';

import {DxForm, DxSimpleItem, DxGroupItem,} from 'devextreme-vue/form';

import { DxRequiredRule, DxEmailRule, DxAsyncRule } from 'devextreme-vue/form';

import axios, {AxiosError} from 'axios';
import { ValidationCallbackData } from 'devextreme/ui/validation_rules';
import {ref} from 'vue';

import DataSource from "devextreme/data/data_source";
import CustomStore from 'devextreme/data/custom_store';
import { resolveSoa } from "dns";
import { CustomSummaryInfo } from "devextreme/ui/data_grid";

let formData: any = ref({});

let citySuggestions = ref([]);


function saveOrder(){
  console.log("saveOrder");
}


function formatPrice(price){
  return parseFloat(price.value).toFixed(2).replace(".", ",") + " €";
}

function calculateSummary(options: CustomSummaryInfo){
  options.totalValue = orderItems.reduce(
    (sum, orderItem) => sum + orderItem.quantity*orderItem.product.price,
    0).toFixed(2).replace(".", ",") + " €";
}

function validateAsync(params: ValidationCallbackData){
  const data = {...formData.value};
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

  if(cityZips.includes(zip)){
    suggestion.push('Darmstadt');
  }
  if(zip in subUrbZips){
    suggestion = suggestion.concat(subUrbZips[zip])
  }
  if(suggestion.length === 1){
    formData.value.city = suggestion[0];
  }
  citySuggestions.value.length = 0;
  suggestion.forEach((s) => {
    citySuggestions.value.push(s);
  })
}

interface Product{
  name: string;
  price: number;
  sku: string;
  panels: number;
  url: string;
  description: string;
  id: number;
}

interface OrderItem{
  product: Product, 
  quantity: number,
  id: number
}


let orderItems: Array<OrderItem> = [];

let orderItemsDatasource = new DataSource({
  store: new CustomStore({
    key: 'id',
    load: (options) => {
      if(orderItems.length === 0){
        return axios.get('api/products',{
          params: options
        }).then(response =>  {
          return orderItems = (response.data as Array<Product>).map(product => {
            return {
              product: product, 
              quantity: 0,
              id: product.id
            }
          }) as Array<OrderItem>
        });
      }
      return new Promise((resolve, reject) => {
        resolve(orderItems);
      });
    },
    update: (key, values: OrderItem) => {
      return new Promise((resolve, reject) => {
        let item: OrderItem|undefined = orderItems.find((orderItem => orderItem.id === key));
        if(item === undefined){
          reject("item not found");
        }
        if(values.quantity === null){
          values.quantity = 0;
        }
        item.quantity = values.quantity;
        return resolve(item);
      })
    },
    byKey: (key) => {
      return new Promise((resolve, reject) => {
        let item: OrderItem|undefined = orderItems.find((orderItem => orderItem.id === key));
        if(item === undefined){
          reject("item not found");
        }
        return resolve(item);
      })
    }
  })
})

</script>
