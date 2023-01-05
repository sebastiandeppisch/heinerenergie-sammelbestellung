<template>
  <div>
    <h2 class="content-block">Berater*innen</h2>
    <div style="margin: 30px 40px 30px 40px;">
      <DxDataGrid
        class="dx-card wide-card"
        :data-source="users"
        :show-borders="false"
        :column-auto-width="true"
      >
        <DxEditing
          :allow-updating="true"
          :allow-adding="true"
          :allow-deleting="true"
          mode="cell"
        />
        <DxColumn data-field="first_name" caption="Vorname" />
        <DxColumn data-field="last_name" caption="Nachname" sort-order="asc"/>
        <DxColumn data-field="email" caption="E-Mail Adresse" :editor-options="{mode: 'email'}"/>
        <DxColumn data-field="is_admin" caption="Admin" />
      </DxDataGrid>
    </div>
  </div>
</template>

<script setup lang="ts">

import LaravelDataSource from '../LaravelDataSource'
import DxDataGrid, {
  DxColumn,
  DxEditing
} from "devextreme-vue/data-grid";
import LaravelLookupSource from '../LaravelLookupSource';

const users = new LaravelDataSource("api/users");
</script>
