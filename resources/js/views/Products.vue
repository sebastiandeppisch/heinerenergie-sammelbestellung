<template>
  <div ref="outer">
    <h2 class="content-block">Artikel</h2>
    <div style="margin: 30px 40px 30px 40px;">
    <DxDataGrid
      class="dx-card wide-card"
      :data-source="r.productStore"
      :show-borders="false"
      :column-auto-width="true"
      :height="reactiveHeight.height"
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
      <DxToolbar>
        <DxItem
          location="before"
          template="selectBulkOrder"
        />
        <DxItem
          location="before"
          template="bulkorders"
        />
        <DxItem
          location="after"
          name="addRowButton"
        />
      </DxToolbar>
      <template #selectBulkOrder>
        <DxSelectBox
          :data-source="bulkOrders"
          display-expr="name"
          value-expr="id"
          v-model="r.selectedBulkOrder"
          :on-value-changed="bulkOrderChanged"
        />        
      </template>
      <template #bulkorders>
        <DxButton
          icon="fields"
          text="Sammelbestellungen editieren"
          @click="openBulkOrders"
        />        
      </template>
      <DxColumn data-field="product_category_id" caption="Kategorie">
        <DxLookup
          :data-source="r.productCategories"
          display-expr="name"
          value-expr="id"
        />
      </DxColumn>
      <DxColumn data-field="name" caption="Name" />
      <DxColumn data-field="price" caption="Preis" :editor-options="priceEditorOptions" :customize-text="formatPrice" />
      <DxColumn data-field="sku" caption="SKU" />
      <DxColumn data-field="is_supplier_product" caption="Von Lieferant" />
      <DxColumn data-field="panelsCount" caption="Panels" :editor-options="panelsCountEditorOptions"/>
      <DxColumn data-field="description" caption="Beschreibung" width="600px"/>
      <DxMasterDetail
        :enabled="true"
        template="masterDetailTemplate"
      />
      <template #masterDetailTemplate="{data}">
        <ProductTableDetail :product="data.data" />
      </template>
    </DxDataGrid>
    <DxPopup
      v-model:visible="r.popupVisible"
      title="Sammelbestellungen"
      hide-on-outside-click="true"
      :show-close-button="true"
      :width="800"
      :height="600"
    >
      <BulkOrders />
    </DxPopup>
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
  DxMasterDetail,
  DxFormItem,
  DxForm,
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource'
import LaravelLookupSource from '../LaravelLookupSource'
import { ref, onMounted, reactive } from 'vue'
import {AdaptTableHeight} from '../helpers'
import ProductTableDetail from "./ProductTableDetail.vue";
import BulkOrders from "./BulkOrders.vue";
import DxPopup from 'devextreme-vue/popup';
import DxButton from "devextreme-vue/button";
import DxSelectBox from 'devextreme-vue/select-box';

const bulkOrders = new LaravelDataSource("api/bulkorders");

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
const reactiveHeight = tableHeight.getReactive();

const r = reactive({
  popupVisible: false,
  selectedBulkOrder: null,
  productStore: null,
  productCategories: null
});

onMounted(() => {
  tableHeight.calcHeight();
  bulkOrders.load().then((data) => {
    const notArchivedBulkOrders = data.filter(item => !item.archived);
    if(notArchivedBulkOrders.length === 1) {
      r.selectedBulkOrder= notArchivedBulkOrders[0].id;
    }
  });
});

function openBulkOrders(){
  r.popupVisible = true;
}

function bulkOrderChanged(){
  const bulkOrder = r.selectedBulkOrder;
  if(bulkOrder !== null){
    r.productStore = new LaravelDataSource('api/bulkorders/'+ bulkOrder + '/products');
    r.productCategories = new LaravelLookupSource('api/bulkorders/'+ bulkOrder + '/productcategories');
  }
 
}
</script>
