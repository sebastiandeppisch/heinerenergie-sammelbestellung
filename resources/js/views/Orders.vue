<template>
  <div>
    <h2 class="content-block">Bestellungen</h2>

    <DxDataGrid
      class="dx-card wide-card"
      data-source="api/orders"
      :show-borders="false"
      :column-auto-width="true"
      :column-hiding-enabled="true"
      key-expr="id"
    >
      <DxColumn data-field="firstName" caption="Vorname" />
      <DxColumn data-field="lastName" caption="Nachname" />
      <DxColumn data-field="panelCount" caption="Anzahl Module" />
      <DxColumn data-field="price" caption="Gesamtpreis" />
    </DxDataGrid>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import "devextreme/data/odata/store";
import DxDataGrid, {
  DxColumn,
  DxEditing
} from "devextreme-vue/data-grid";

const testData = [];
</script>
