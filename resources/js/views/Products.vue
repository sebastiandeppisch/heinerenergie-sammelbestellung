<template>
  <div>
    <h2 class="content-block">Produkte</h2>

    <DxDataGrid
      class="dx-card wide-card"
      data-source="api/products"
      :show-borders="false"
      :column-auto-width="true"
      key-expr="id"
    >
      <DxEditing
        :allow-updating="true"
        :allow-adding="true"
        :allow-deleting="true"
        mode="cell"
      />
      <DxColumn data-field="name" caption="Name" />
      <DxColumn data-field="price" caption="Preis" />
      <DxColumn data-field="sku" caption="SKU" />
      <DxColumn data-field="panelsCount" caption="Panels" />
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
</script>
