<template>
  <DxDataGrid
    :data-source="bulkorderStore"
    :on-editing-start="onEditingStart"
    :on-init-new-row="onInitNewRow"
  >
    <DxEditing
      :allow-updating="true"
      :allow-adding="true"
      mode="form"
    >
      <DxForm
        :customize-item="customizeItem"
      />
    </DxEditing>
    <DxColumn
      data-field="name"
      caption="Name"
    />
    <DxColumn
      data-field="archived"
      caption="Archiviert"
    />
    <DxColumn
      :visible="false"
      data-field="copy_from"
      caption="Artikel duplizieren aus"
    >
      <DxFormItem
        editor-type="dxLookup"
        :editor-options="{
          dataSource: 'api/bulkorders',
          valueExpr: 'id',
          displayExpr: 'name',
          showClearButton: true
        }"
        :customize-item="customizeItem"
      />
    </DxColumn>
  </DxDataGrid>
</template>

<script setup>
import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxLookup,
  DxFormItem,
  DxForm
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource'
import { ref, onMounted } from 'vue'

const bulkorderStore = new LaravelDataSource("api/bulkorders");

let isEditing = false;

function onInitNewRow(){
  isEditing = false;
}

function onEditingStart(){
  isEditing = true;
}

function customizeItem(item){
  if(item.name === 'copy_from'){
    item.visible = !isEditing;
  }
}
</script>
