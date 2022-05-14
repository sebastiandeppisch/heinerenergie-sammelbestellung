<template>
  <div>
    <h2 class="content-block">Bestellungen</h2>

    <DxDataGrid
      class="dx-card wide-card"
      :data-source="ordersStore"
      :show-borders="false"
      :column-auto-width="true"
      :column-hiding-enabled="true"
    >
      <DxColumn data-field="firstName" caption="Vorname" />
      <DxColumn data-field="lastName" caption="Nachname" />
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
            display-format="Panels gesamt: {0}"
          />
      </DxSummary>
    </DxDataGrid>
  </div>
</template>

<script setup lang="ts">

import LaravelDataSource from '../LaravelDataSource'
import {formatPriceCell, formatPrice} from '../helpers'
import { ref, onMounted } from 'vue'
import { CustomSummaryInfo } from "devextreme/ui/data_grid";
import DxDataGrid, {
  DxColumn,
  DxEditing, 
  DxSummary,
  DxTotalItem
} from "devextreme-vue/data-grid";

type Order = App.Models.Order;

const ordersStore = new LaravelDataSource("api/orders");

</script>
