<template>
  <div ref="outer">
    <h2 class="content-block">Artikel</h2>
    <div style="margin: 30px 40px 30px 40px;">
    <DxDataGrid
      class="dx-card wide-card"
      :data-source="productStore"
      :show-borders="false"
      :column-auto-width="true"
      :height="r.height"
		  min-height="450px"
    >
      <DxScrolling
        mode="virtual"
      />
      <DxFilterRow  :visible="true"/>
      <DxEditing
        :allow-updating="true"
        :allow-adding="true"
        :allow-deleting="true"
        mode="cell"
      />
      <DxColumn data-field="product_category_id" caption="Kategorie">
        <DxLookup
          :data-source="productCategories"
          display-expr="name"
          value-expr="id"
        />
      </DxColumn>
      <DxColumn data-field="name" caption="Name" />
      <DxColumn data-field="price" caption="Preis" :editor-options="priceEditorOptions" :customize-text="formatPrice" />
      <DxColumn data-field="sku" caption="SKU" />
      <DxColumn data-field="panelsCount" caption="Panels" :editor-options="panelsCountEditorOptions"/>
      <DxColumn data-field="url" caption="URL" />
      <DxColumn data-field="description" caption="Beschreibung" width="600px"/>
      <DxMasterDetail
        :enabled="true"
        template="masterDetailTemplate"
      />
      <template #masterDetailTemplate="{data}">
        <ProductTableDetail :product="data.data" />
      </template>
    </DxDataGrid>
    </div>
  </div>
</template>

<script setup>
import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxScrolling,
  DxLookup,
  DxItem,
  DxToolbar,
  DxFilterRow,
  DxMasterDetail
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource'
import LaravelLookupSource from '../LaravelLookupSource'
import { ref, onMounted } from 'vue'
import {AdaptTableHeight} from '../helpers'
import ProductTableDetail from "./ProductTableDetail.vue";


const productStore = new LaravelDataSource("api/products");

const productCategories = (new LaravelLookupSource('api/productcategories'))

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

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const r = tableHeight.getReactive();

onMounted(() => {
  tableHeight.calcHeight();
});
</script>
