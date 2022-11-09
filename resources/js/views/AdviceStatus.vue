<template>
  <DxDataGrid
    :data-source="adviceStatus"
  >
    <DxEditing
      :allow-updating="true"
      :allow-adding="true"
      :allow-deleting="true"
      mode="cell"
    />
    <DxColumn
      data-field="name"
      caption="Name"
    />
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

const adviceStatus = new LaravelDataSource("api/advicestatus");

</script>
