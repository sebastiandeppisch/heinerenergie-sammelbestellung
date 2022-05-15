<template>
  <div>
    <div v-if="state.order === null">
      <h2 class="content-block">Neue Bestellung</h2>
      <div style="margin:30px;">
      <div class="dx-card" style="padding:30px;margin: 10px;">
        <OrderForm
          :order="formData"
          :confirm-email="true"
          :update-button="false"
          ref="orderForm"
        />
      </div>
    <div class="dx-card" style="padding:30px;margin: 10px;">
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
        caption="Artikel"
        data-field="product.name"
        :allow-sorting="false"
        :allow-editing="false"
      />
      <DxColumn
        caption="Preis"
        data-field="product.price"
        :customize-text="formatPriceCell"
        :allow-sorting="false"
        :allow-editing="false"

      />
      <DxColumn
        caption="Anzahl"
        data-field="quantity"
        :allow-sorting="false"
        data-type="number"
        :editor-options="{defaultValue: 0, min: 0, showSpinButtons: true, showClearButton: true}"
        :show-editor-always="true"
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
    </div>


<div style="margin: 10px;margin-top:20px">
<DxButton 
      text="Bestellung absenden"
      type="default"
      width="100%"
      @click="saveOrder"
    />
</div>
    
  </div></div>
  <div v-else>
    <OrderSaved :order="state.order" />
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


import {DxForm, DxSimpleItem, DxGroupItem,} from 'devextreme-vue/form';

import { DxRequiredRule, DxEmailRule, DxAsyncRule } from 'devextreme-vue/form';

import axios, {AxiosError} from 'axios';
import { ValidationCallbackData } from 'devextreme/ui/validation_rules';
import {ref, reactive, computed, onMounted} from 'vue';

import DataSource from "devextreme/data/data_source";
import CustomStore from 'devextreme/data/custom_store';
import { resolveSoa } from "dns";
import { CustomSummaryInfo } from "devextreme/ui/data_grid";
import notify from 'devextreme/ui/notify';
import dxForm from 'devextreme/ui/form';

import OrderForm from '../components/OrderForm.vue'
import OrderSaved from '../components/OrderSaved.vue'

import {formatPrice, formatPriceCell, notifyError} from './../helpers'
import { ValidationResult } from 'devextreme/ui/validation_group';


type Product = App.Models.Product;
type Order = App.Models.Order;

let formData: any = ref({});

let orderItems: Array<OrderItem> = [];

interface State{
  order: null|Order
}

const state: State = reactive({
  order: null
})

const orderForm = ref(null);

function articlesAreEmpty(): boolean{
  const count = orderItems.reduce(
    (sum, orderItem) => sum + orderItem.quantity,
    0)
  return count <= 0;
}

function saveOrder(){
  const validated = orderForm.value.validate() as Promise<ValidationResult>
  validated.then(result => {
    if(!result.isValid){
      notify('Bitte fülle zuerst alle Felder aus', 'error');
      return;
    }
    if(articlesAreEmpty()){
      notify('Deine Bestellung ist aktuell noch leer. Bitte füge Artikel hinzu', 'error');
      return;
    }
    axios.post('api/orders', {
      orderItems, 
      ...(formData.value)
    }).then((response) => {
      state.order = response.data as Order;
    }).catch(error => {
      notifyError(error)
    })
  });
}

function calculateSummary(options: CustomSummaryInfo){
  options.totalValue = formatPrice(orderItems.reduce(
    (sum, orderItem) => sum + orderItem.quantity*orderItem.product.price,
    0))
}


interface OrderItem{
  product: Product,
  quantity: number,
  id: number
}



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
