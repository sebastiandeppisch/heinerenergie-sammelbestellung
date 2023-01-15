<template>
  <div class="flex-row">
    <div class="dx-card flex-cell">
      <div v-if="order.archived">
        <h2 class="content-block">Bestellung archiviert</h2>
        <p>Die Bestellung wurde archiviert und kann nicht mehr bearbeitet werden.</p>
      </div>
      <DxDataGrid
        :data-source="orderItemsDatasource"
        :show-borders="false"
        @row-removed="updateData"
        @row-updated="updateData"
			  @row-inserted="updateData"
      >
        <DxEditing
          :allow-updating="!order.archived"
          :allow-adding="!order.archived"
          :allow-deleting="!order.archived"
          mode="cell"
        />
        <DxColumn
          caption="Artikel"
          data-field="product_id"
        >
          <DxLookup
              :data-source="products"
              display-expr="name"
              value-expr="id"
            />
        </DxColumn>
        <DxColumn
            caption="Anzahl"
            data-field="quantity"
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
      <DxTagBox
        :data-source="advisors"
        display-expr="name"
        value-expr="id"
        :on-value-changed="updateAdvisors"
        label="Teilen mit"
        v-model="order.shares_ids"
      ></DxTagBox>

    </div>
     <div class="dx-card flex-cell">
      <OrderForm
        :order="order"
        :confirm-email="false"
        v-on:update="updateData"
        :update-button="true"
        :allow-editing="!order.archived"
      />
      <div>
        <DxButton
          v-if="!order.checked"
          icon="check"
          @click="checkOrder"
          text="Bestellung als geprüft markieren"
          type="success"
          :disabled="order.archived"
        />
        <DxButton
          v-if="order.checked"
          icon="revert"
          @click="uncheckOrder"
          text="Prüfung zurücksetzen"
          type="danger"
          :disabled="order.archived"
        />
        <br>
        <br>
        <DxButton
          icon="email"
          @click="openMailReasonDialog"
          text="Bestellübersicht erneut versenden"
          type="info"
        />
        <DxPopup
          :visible="mailReasonDialogVisible"
          title="Bestellübersicht versenden"
          :show-title="true"
          :drag-enabled="false"
          :close-on-outside-click="true"
          :show-close-button="true"
          width="450px"
          height="auto"
        >
          <div style="margin:5px">
            <DxTextBox
                v-model="mailReason"
                placeholder="Grund für die erneute Versendung"
                width="100%"
                validation="required"
                label="Grund für die erneute Versendung"
              />

              <DxButton
                icon="email"
                @click="sendMail"
                text="Bestellübersicht erneut versenden"
                type="default"
                width="100%"
                style="margin-top: 20px;"
              />
          </div>
        </DxPopup>
      </div>
      <div style="float: right;">
        <DxButton
          icon="trash"
          @click="deleteOrder"
          type="danger"
          :disabled="order.archived"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxSummary,
  DxTotalItem,
  DxLookup
} from "devextreme-vue/data-grid";
import LaravelDataSource from "../LaravelDataSource";
import LaravelLookupSource from "../LaravelLookupSource";
import DataSource from 'devextreme/data/data_source';
import axios from 'axios'
import { CustomSummaryInfo } from "devextreme/ui/data_grid";
import {formatPrice} from '../helpers'
import OrderForm from '../components/OrderForm.vue'
import DxButton from "devextreme-vue/button";

import DxTagBox from "devextreme-vue/tag-box";

import {confirm} from 'devextreme/ui/dialog';
import { defineEmits, defineProps, ref } from "vue";
import DxTextBox from "devextreme-vue/text-box";
import DxPopup from "devextreme-vue/popup";
import notify from "devextreme/ui/notify";

const emit = defineEmits(['update'])

type Order = App.Models.Order;

interface Props {
  order: App.Models.Order
}
const props = defineProps<Props>();

const mailReasonDialogVisible = ref(false);
const mailReason = ref('');

const orderItemsDatasource = new LaravelDataSource('api/orders/' + props.order.id + '/orderitems');
const products = new LaravelLookupSource('api/bulkorders/' + props.order.bulk_order_id + '/products');

const advisors = new LaravelLookupSource('api/users');

function calculateSummary(options: CustomSummaryInfo){
  options.totalValue = formatPrice(props.order.price);
}

function updateData() {
  emit('update')
}

function deleteOrder() {
  confirm('Möchtest Du die Bestellung wirklich löschen?', 'Bestellung löschen')
    .then(() => {
      axios.delete('api/orders/' + props.order.id)
        .then(() => {
          emit('update')
        })
    })
  
}

function checkOrder() {
  axios.post('api/orders/' + props.order.id + '/check')
    .then(() => {
      emit('update')
    })
}

function uncheckOrder() {
  axios.post('api/orders/' + props.order.id + '/uncheck')
    .then(() => {
      emit('update')
    })
}

function updateAdvisors(e: any) {
  axios.post('api/orders/' + props.order.id + '/advisors', {advisors: e.value})
    .then(() => {
      emit('update')
  })
}

function openMailReasonDialog() {
  mailReasonDialogVisible.value = true
}

function sendMail() {
  axios.post('api/orders/' + props.order.id + '/sendmail', {reason: mailReason.value})
    .then(() => {
      mailReasonDialogVisible.value = false
      mailReason.value = ''
      notify('Bestellübersicht wurde versendet', 'success', 3000)
    })
}

</script>
<style scoped>
.flex-row{
  flex-direction: row;
  display: flex;
  width: 100%;
}
.flex-cell{
  padding: 30px;
  flex: 1;
}
</style>