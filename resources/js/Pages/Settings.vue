<script setup lang="ts">
import DxAccordion from 'devextreme-vue/accordion';

import LaravelDataSource from '../LaravelDataSource';

import Setting from './../components/Setting.vue';

const dataSource = new LaravelDataSource('api/settings');
</script>

<template>
    <div>
        <h2 class="content-block">Einstellungen</h2>
        <div style="margin: 30px 40px 30px 40px">
            <DxAccordion :data-source="dataSource" :collapsible="true" :multiple="false">
                <template #title="{ data }">
                    <div style="font-size: 1.5em">{{ data.name }}</div>
                </template>
                <template #item="{ data }">
                    <Setting :setting="data" />
                </template>
            </DxAccordion>
        </div>
    </div>
</template>
