<template>
  <div class="flex-row">
    <div style="flex: 1;" class="dx-card">
      <DxDataGrid
        :data-source="orderItemsDatasource"
        :show-borders="false"
        @row-removed="updateData"
        @row-updated="updateData"
			  @row-inserted="updateData"
      >
        <DxEditing
          :allow-updating="true"
          :allow-adding="true"
          :allow-deleting="true"
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
    </div>
     <div style="flex: 1;" class="dx-card">
      <OrderForm
        :order="order"
        :confirm-email="false"
        v-on:update="updateData"
        :update-button="true"
      />
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

const emit = defineEmits(['update'])

type Order = App.Models.Order;

interface Props {
  order: App.Models.Order
}
const props = defineProps<Props>();

const orderItemsDatasource = new LaravelDataSource('api/orders/' + props.order.id + '/orderitems');
const products = new LaravelLookupSource('api/products');

function calculateSummary(options: CustomSummaryInfo){
  options.totalValue = formatPrice(props.order.price);
}

function updateData() {
  emit('update')
}

</script>
<style scoped>
.flex-row{
  flex-direction: row;
  display: flex;
  width: 100%;
}
</style>