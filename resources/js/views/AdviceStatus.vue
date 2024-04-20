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
    <DxColumn
      data-field="result"
      caption="Ergebnis"
    >
      <DxLookup
        :data-source="adviceStatusResult"
        display-expr="name"
        value-expr="id"
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

const adviceStatus = new LaravelDataSource("api/advicestatus");

const adviceStatusResult = [
  { id: 0, name: "Neu" },
  { id: 1, name: "In Bearbeitung" },
  { id: 2, name: "Erfolgreich beraten" },
  { id: 3, name: "Nicht erfolgreich" }
];

</script>
