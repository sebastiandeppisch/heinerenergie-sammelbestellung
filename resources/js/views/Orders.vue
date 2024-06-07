<script setup lang="ts">

import LaravelDataSource from '../LaravelDataSource'
import OrderDetail from './../components/OrderDetail.vue'
import {formatPriceCell, formatPrice, formatDateCell, AdaptTableHeight} from '../helpers'
import { ref, onMounted, reactive } from 'vue'
import { CustomSummaryInfo , } from "devextreme/ui/data_grid";
import { DxButton } from 'devextreme-vue/button';
import DxSelectBox from 'devextreme-vue/select-box';
import DxDropDownButton from 'devextreme-vue/drop-down-button';
import { store } from "../store";

import DxDataGrid, {
  DxColumn,
  DxEditing, 
  DxSummary,
  DxTotalItem,
  DxMasterDetail,
  DxToolbar,
  DxItem,
  DxLookup,
  DxFilterRow,
  DxScrolling
} from "devextreme-vue/data-grid";
import LaravelLookupSource from '../LaravelLookupSource';

const isAdmin = store.state.user.is_admin;
type Order = App.Models.Order;

//const ordersStore = new LaravelDataSource("api/orders");
const advisors  = new LaravelLookupSource("api/users");
const bulkorders  = new LaravelLookupSource("api/bulkorders");
const exportTypes = [{
  id: 'supplier',
  name: 'Lieferanten-Artikel'
}, {
  id: 'own',
  name: 'heiner*energie Artikel'
}, {
  id: 'all',
  name: 'Alle Artikel'
}];

interface State{
  bulkOrderId: number | null;
  ordersStore: LaravelDataSource | null;
}

const state: State = reactive({
  bulkOrderId: null,
  ordersStore: null
})

function update(){
  console.log("update from order");
  state.ordersStore.reload();
}

function exportOrders(e){
  window.open('bulkorders/'+ state.bulkOrderId + '/orderexport?products=' + e.itemData.id, '_blank').focus();
}


const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const r = tableHeight.getReactive();

onMounted(() => {
  tableHeight.calcHeight();
  bulkorders.load().then((data) => {
    console.log(data);
    const notArchivedBulkOrders = data.filter(item => !item.archived);
    if(notArchivedBulkOrders.length === 1) {
      state.bulkOrderId = notArchivedBulkOrders[0].id;
      console.log(state.bulkOrderId);
    }
  });
});

function bulkOrderChanged(){
  const bulkOrder = state.bulkOrderId;
  if(bulkOrder !== null){
    state.ordersStore = new LaravelDataSource('api/bulkorders/'+ bulkOrder + '/orders');
  }
}
</script>

<template>
  <div ref="outer">
    <h2 class="content-block">Bestellungen</h2>
    <div class="main-table">
    <DxDataGrid
      class="dx-card wide-card"
      :data-source="state.ordersStore"
      :show-borders="false"
      :column-auto-width="true"
      :column-hiding-enabled="true"
      :height="r.height"
		  min-height="450px"
    >
      <DxScrolling
        mode="virtual"
      />
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
      <DxColumn data-field="created_at" caption="Eingegangen" :customize-text="formatDateCell"/>
      <DxColumn data-field="checked" caption="Geprüft" />
      <DxColumn data-field="price" caption="Gesamtpreis" :customize-text="formatPriceCell" />

       <DxSummary
        :recalculate-while-editing="true"
        >
          <DxTotalItem
            column="firstName"
            summary-type="count"
          />
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
          location="before"
          template="selectBulkOrder"
        />
        <DxItem
          template="exportTemplate"
          v-if="isAdmin"
        />
      </DxToolbar>
      <template #orderTemplate="{ data }">
        <OrderDetail
          :order="data.data"
          v-on:update="update"
        />
      </template>
      <template #selectBulkOrder>
        <DxSelectBox
          :data-source="bulkorders"
          display-expr="name"
          value-expr="id"
          v-model="state.bulkOrderId"
          :on-value-changed="bulkOrderChanged"
          label="Sammelbestellung"
        />        
      </template>
      <template #exportTemplate>
        <DxDropDownButton
          :drop-down-options="{ width: 180 }"
          :items="exportTypes"
          icon="exportxlsx"
          hint="Bestellungen exportieren"
          @item-click="exportOrders"
          display-expr="name"
          key-expr="id"
        />
      </template>
    </DxDataGrid>
    </div>
  </div>
</template>
<style scoped>
@media screen and (min-width:680px) {
  .main-table{
    margin: 30px 40px 30px 40px
  }

}
</style>