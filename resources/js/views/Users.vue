<template>
  <div ref="outer">
    <h2 class="content-block">Berater*innen</h2>
    <div style="margin: 30px 40px 30px 40px;">
      <DxDataGrid
        class="dx-card wide-card"
        :data-source="users"
        :show-borders="false"
        :column-auto-width="true"
        :height="r.height"
      >
        <DxScrolling
          mode="virtual"
        />
        <DxEditing
          :allow-updating="true"
          :allow-adding="true"
          :allow-deleting="false"
          mode="cell"
        />
        <DxColumn data-field="first_name" caption="Vorname" />
        <DxColumn data-field="last_name" caption="Nachname" sort-order="asc"/>
        <DxColumn data-field="email" caption="E-Mail Adresse" :editor-options="{mode: 'email'}"/>
        <DxColumn data-field="is_admin" caption="Admin" />
        <DxSummary>
          <DxTotalItem column="first_name" summary-type="count" />
        </DxSummary>
      </DxDataGrid>
    </div>
  </div>
</template>

<script setup lang="ts">

import LaravelDataSource from '../LaravelDataSource'
import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxScrolling,
  DxSummary,
  DxTotalItem
} from "devextreme-vue/data-grid";
import LaravelLookupSource from '../LaravelLookupSource';
import { ref, onMounted } from "vue";
import {AdaptTableHeight} from '../helpers'


const users = new LaravelDataSource("api/users");

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const r = tableHeight.getReactive();

onMounted(() => {
  tableHeight.calcHeight();
});
</script>
