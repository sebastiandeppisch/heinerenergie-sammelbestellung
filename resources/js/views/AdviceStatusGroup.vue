<script setup lang="ts">
import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxLookup,
  DxFilterRow
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource';
import {  computed, } from 'vue';
import notify from 'devextreme/ui/notify';
import LaravelLookupSource from "@/LaravelLookupSource";

// Define props for the component
const props = defineProps<{
  group: App.Data.GroupData
  groups: App.Data.GroupData[]
}>();

type AdviceStatusData = App.Data.AdviceStatusData;

// Get group ID from props
const groupId = computed(() => props.group.id.toString());

// own status with full editing
const ownAdviceStatusSource = computed(() => {
  const source = new LaravelDataSource(`/api/groups/${groupId.value}/advicestatus`);
  source.filter(['group_id', '=', props.group.id]);
  return source;
});

// all status only for the visibility flag
const otherAdviceStatusSource = computed(() => {
  return new LaravelDataSource(`/api/groups/${groupId.value}/advicestatus`);
});

// Results enum for the dropdown
const adviceStatusResult = [
  { id: 0, name: "Neu" },
  { id: 1, name: "In Bearbeitung" },
  { id: 2, name: "Erfolgreich beraten" },
  { id: 3, name: "Nicht erfolgreich" }
];

// Holen wir die Gruppen für das Lookup
const groupsSource = computed(() => {
  return props.groups.map(group => ({
    id: group.id,
    name: group.name
  }));
});

// Event Handler für Zellwertänderungen im zweiten DataGrid
function onVisibilityChanged(e: any) {
    console.log(e);
  if (e.dataField === 'visible_in_group') {
    notify(`Status Sichtbarkeit wurde ${e.value ? 'aktiviert' : 'deaktiviert'}.`, 'success', 3000);
  }
}

function reload() {
    ownAdviceStatusSource.value.reload();
    otherAdviceStatusSource.value.reload();
}
</script>

<template>
  <div class="flex flex-col gap-4 mt-4">
    <div>
    <h3 class="mt-4">Beratungszustände</h3>
    <p class="text-sm text-gray-500">
        Du kannst hier die Beratungszustände verwalten. Die Zustände werden bei allen Unter-Gruppen zur Verfügung gestellt, können aber ausgeblendet werden.
    </p>
    <DxDataGrid
      :data-source="ownAdviceStatusSource"
      :cell-hint-enabled="true"
      @saved="reload"
    >
      <DxEditing
        :allow-updating="true"
        :allow-adding="true"
        :allow-deleting="true"
        mode="row"
      />
      <DxColumn
        data-field="name"
        caption="Name"
        sort-order="asc"
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
      <DxColumn
        data-field="group_id"
        caption="Initiative"
        :visible="false"
        :allow-editing="false"
        :default-value="props.group.id"
      />
    </DxDataGrid>
    </div>
    <div>
    <h3 class="mt-4">Verwendete Beratungszustände</h3>
    <p class="text-sm text-gray-500">
        Hier kannst Du für diese Gruppe festlegen, welche Beratungszustände von den Berater:innen verwendet werden sollen.
    </p>
    <DxDataGrid
      :data-source="otherAdviceStatusSource"
      :cell-hint-enabled="true"
      @saved="reload"
    >
      <DxEditing
        :allow-updating="true"
        :allow-adding="false"
        :allow-deleting="false"
        mode="cell"
      />
      <DxColumn
        data-field="name"
        caption="Name"
        :allow-editing="false"
        sort-order="asc"
      />
      <DxColumn
        data-field="group_id"
        caption="Initiative"
        :allow-editing="false"
      >
        <DxLookup
          :data-source="groupsSource"
          display-expr="name"
          value-expr="id"
        />
      </DxColumn>
      <DxColumn
        data-field="visible_in_group"
        caption="Sichtbar"
        data-type="boolean"
        @cell-value-changed="onVisibilityChanged"
      />
    </DxDataGrid>
    </div>
  </div>
</template>
