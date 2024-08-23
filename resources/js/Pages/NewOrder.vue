<script setup lang="ts">
import DxTextBox from 'devextreme-vue/text-box';
import LaravelLookupSource from '../LaravelLookupSource'

import DxButton from 'devextreme-vue/button';

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxSummary,
  DxTotalItem,
  DxLookup
} from "devextreme-vue/data-grid";

import PublicLayout from './../layouts/PublicLayout.vue';

defineOptions({
  layout: PublicLayout
})


import axios from 'axios';
import {ref, reactive, computed, onMounted} from 'vue';

import DataSource from "devextreme/data/data_source";
import CustomStore from 'devextreme/data/custom_store';
import { CustomSummaryInfo } from "devextreme/ui/data_grid";
import notify from 'devextreme/ui/notify';
import ProductDetail from '../components/ProductDetail.vue'

import OrderForm from '../components/OrderForm.vue'
import OrderSaved from '../components/OrderSaved.vue'

import {formatPrice, formatPriceCell, notifyError} from '../helpers'
import { ValidationResult } from 'devextreme/ui/validation_group';

import { useStore } from '../store';
import auth from '../auth';
import SettingHtml from '../components/SettingHtml.vue';
import { usePage } from '@inertiajs/vue3';

type Product = App.Models.Product;
type Order = App.Models.Order;

let formData: any = ref({});

const blocked = ref(true);

let password = "";

auth.initLogin().then(done => {
  if(auth.loggedIn()){
    blocked.value = false;
  }
});

try{
  const urlParams = new URLSearchParams(window.location.search);
  const formdata = urlParams.get('formdata');

  if (formdata) {
    const defaultData = JSON.parse(formdata) as Order;
    formData.value = defaultData;
  }
}catch(e){
  window.setTimeout(() =>  {
    notify('Ungültige URL-Parameter', 'error');
  }, 1000);
}

const email = computed(() => {
  console.log(useStore().getters.email);
  return useStore().getters.email;
})

const advisorUrl = computed(() => {
  return window.location.protocol + "//" + window.location.hostname + "/sammelbestellung?" + (new URLSearchParams({formdata: JSON.stringify({advisorEmail: useStore().getters.email})}).toString()) 
})



let orderItems: Array<OrderItem> = [];

interface State{
  order: null|Order,
  hideElements: boolean
}

const state: State = reactive({
  order: null,
  hideElements: false
})

const orderForm = ref();

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
      ...(formData.value),
      password
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
          params: {...options, password}
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

let productCategories = () => new LaravelLookupSource('api/productcategories?password=' + password)

let orderFormText = () => 'orderFormText?password=' + password;

function checkWidth(){
  state.hideElements = window.innerWidth < 550;
}
window.onresize = () => {
  checkWidth();
}
checkWidth();

function passwordChanged(data){
  axios.get('api/checkpassword', {
    params: {
      password : data.value
    }
  }).then(response => {
    password = data.value;
    blocked.value = false;
  }).catch(e =>  {});
}
</script>

<template>
  <div>
    <Transition name="slide-up">
    <div v-if="state.order === null">
      <h2 class="content-block">Sammelbestellung</h2>
      <div class="main">
      <Transition name="slide-up">
      <div class="dx-card content" style="padding: 30px" v-if="blocked">
        <DxTextBox
            mode="password"
            value-change-event="keyup"
            @value-changed="passwordChanged"
            :is-valid="!blocked"
            :element-attr="{autocomplete: 'off'}"
            placeholder="Gib bitte das Passwort ein, um Zugriff zum Bestellformular zu erhalten"
        />
      </div>
      <div v-else>
      <p class="dx-card content" style="padding:30px" v-if="email !== null && email !== undefined">
        Sende folgenden Link an Deine Interessent*innen, damit die Berater*in E-Mail Adresse bereits vorausgefüllt ist: <br>
        <a :href="advisorUrl"><b>{{ advisorUrl }}</b></a>
      </p>
      <p class="dx-card content" style="padding:30px" >
        <SettingHtml :setting="orderFormText()" />
      </p>
      <div class="dx-card content">
        <OrderForm
          :order="formData"
          :confirm-email="true"
          :update-button="false"
          ref="orderForm"
          :allow-editing="true"
        />
      </div>
    <div class="dx-card content">
    <DxDataGrid
      :data-source="orderItemsDatasource"
      :column-hiding-enabled="true"
      :paging="{enabled: false}"
    >
      <DxEditing
        :allow-updating="true"
        :allow-adding="false"
        :allow-deleting="false"
        mode="cell"
      />
      <DxColumn
        caption="Kategorie"
        :group-index="0"
        data-field="product.product_category_id"
      >
        <DxLookup
          :data-source="productCategories"
          display-expr="name"
          value-expr="id"
        />
      </DxColumn>
      <DxColumn
        caption="Artikel"
        data-field="product.name"
        :allow-sorting="false"
        :allow-editing="false"
        :hiding-priority="1"
        cell-template="orderTemplate"
      />
      <DxColumn
        caption="Preis"
        data-field="product.price"
        :customize-text="formatPriceCell"
        :allow-sorting="false"
        :allow-editing="false"
        :hiding-priority="0"
        :width="100"
      />
      <DxColumn
        caption="Anzahl"
        data-field="quantity"
        :allow-sorting="false"
        data-type="number"
        :editor-options="{defaultValue: 0, min: 0, showSpinButtons: true, showClearButton: !state.hideElements}"
        :show-editor-always="true"
        :hiding-priority="1"
        :width="160"
      />
      <template #orderTemplate="{ data }">
        <ProductDetail :product="data.data.product" />
      </template>

      <DxSummary
        :recalculate-while-editing="true"
        :calculate-custom-summary="calculateSummary"
        >
          <DxTotalItem
            name="priceSum"
            summary-type="custom"
            :display-format="state.hideElements?'{0}':'Summe: {0}'"
            show-in-column="quantity"
          />
      </DxSummary>
    </DxDataGrid>

    <p style="text-align:right;font-size:0.7em;float:right" >Bitte beachte, dass sich die Preise noch ändern können. Den verbindlichen Preis erhälst Du von unserem Lieferanten, nachdem wir die Sammelbestellung übermittelt haben.</p>
    </div>


<div style="margin: 10px;margin-top:20px">
<DxButton 
      text="An der Sammelbestellung teilnehmen"
      type="default"
      width="100%"
      @click="saveOrder"
    />
</div>
</div>
</Transition>
    
  </div></div>
  <div v-else>
    <OrderSaved :order="state.order" />
  </div>
  </Transition>
  </div>
</template>
<style scoped>
@media screen and (min-width:680px) {
  .main{
    margin:30px;
  }
  .content{
    padding:30px;
    margin: 10px;
  }
}
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.25s ease-out;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}
</style>