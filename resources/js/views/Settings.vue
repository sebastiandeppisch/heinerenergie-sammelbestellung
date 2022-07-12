<template>
  <div>
    <h2 class="content-block">Einstellungen</h2>
     <DxAccordion
      :data-source="new LaravelDataSource('api/settings')"
      :collapsible="true"
      :multiple="false"
    >
      <template #title="{ data }">
        <div style="font-size: 1.5em;">{{ data.name}}</div>
      </template>
      <template #item="{ data }">
        <Setting :setting="data"/>
      </template>
    </DxAccordion>
  </div>
</template>

<script setup lang="ts">
import DxAccordion from 'devextreme-vue/accordion';

import LaravelDataSource from '../LaravelDataSource';

import Setting from './../components/Setting.vue';

</script>
