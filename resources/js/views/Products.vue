<template>
  <div>
    <h2 class="content-block">Produkte</h2>

    <DxDataGrid
      class="dx-card wide-card"
      :data-source="productStore"
      :show-borders="false"
      :column-auto-width="true"
    >
      <DxEditing
        :allow-updating="true"
        :allow-adding="true"
        :allow-deleting="true"
        mode="cell"
      />
      <DxColumn data-field="name" caption="Name" />
      <DxColumn data-field="price" caption="Preis" :editor-options="priceEditorOptions" :customize-text="formatPrice" />
      <DxColumn data-field="sku" caption="SKU" />
      <DxColumn data-field="panelsCount" caption="Panels" :editor-options="panelsCountEditorOptions"/>
      <DxColumn data-field="url" caption="URL" />
      <DxColumn data-field="description" caption="description" width="600px"/>
    </DxDataGrid>
  </div>
</template>

<script setup>
import DxDataGrid, {
  DxColumn,
  DxEditing
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource'

const productStore = new LaravelDataSource("api/products");

const priceEditorOptions = {
  format: { style: "currency", currency: "EUR", useGrouping: true },
  min: 0
}

const panelsCountEditorOptions = {
  min: 0
}

function formatPrice(price){
  return parseFloat(price.value).toFixed(2).replace(".", ",") + " €";
}
</script>
