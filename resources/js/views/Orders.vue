<template>
  <div>
    <h2 class="content-block">Bestellungen</h2>
    <div style="margin: 30px 40px 30px 40px;">
    <DxDataGrid
      class="dx-card wide-card"
      :data-source="ordersStore"
      :show-borders="false"
      :column-auto-width="true"
      :column-hiding-enabled="true"
    >
      <DxFilterRow  :visible="true"/>
      <DxColumn data-field="firstName" caption="Vorname" />
      <DxColumn data-field="lastName" caption="Nachname" />
      <DxColumn data-field="advisor_id" caption="Berater*in">
        <DxLookup
            :data-source="advisors"
            display-expr="name"
            value-expr="id"
          />
      </DxColumn>
      <DxColumn data-field="panelsCount" caption="Anzahl Module" />
      <DxColumn data-field="price" caption="Gesamtpreis" :customize-text="formatPriceCell" />

       <DxSummary
        :recalculate-while-editing="true"
        >
          <DxTotalItem
            column="price"
            summary-type="sum"
            value-format="currency"
            display-format="Gesamtpreis: {0}"
          />
          <DxTotalItem
            column="panelsCount"
            summary-type="sum"
            display-format="Module gesamt: {0}"
          />
      </DxSummary>
      <DxMasterDetail
        :enabled="true"
        template="orderTemplate"
      />
      <DxToolbar>
        <DxItem
          template="exportTemplate"
        />
      </DxToolbar>
      <template #orderTemplate="{ data }">
        <OrderDetail
          :order="data.data"
          v-on:update="update"
        />
      </template>
      <template #exportTemplate>
      <DxButton
        icon="exportxlsx"
        @click="exportOrders"
      />
    </template>
    </DxDataGrid>
    </div>
  </div>
</template>

<script setup lang="ts">

import LaravelDataSource from '../LaravelDataSource'
import OrderDetail from './../components/OrderDetail.vue'
import {formatPriceCell, formatPrice} from '../helpers'
import { ref, onMounted } from 'vue'
import { CustomSummaryInfo , } from "devextreme/ui/data_grid";
import { DxButton } from 'devextreme-vue/button';

import DxDataGrid, {
  DxColumn,
  DxEditing, 
  DxSummary,
  DxTotalItem,
  DxMasterDetail,
  DxToolbar,
  DxItem,
  DxLookup,
  DxFilterRow
} from "devextreme-vue/data-grid";
import LaravelLookupSource from '../LaravelLookupSource';

type Order = App.Models.Order;

const ordersStore = new LaravelDataSource("api/orders");
const advisors  = new LaravelLookupSource("api/users");


function update(){
  console.log("update from order");
  ordersStore.reload();
}

function exportOrders(){
  window.open('/orderexport', '_blank').focus();
}

</script>
