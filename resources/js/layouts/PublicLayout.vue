<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import DxButton from "devextreme-vue/button";
import DxDrawer from "devextreme-vue/drawer";
import DxScrollView from "devextreme-vue/scroll-view";
import DxToolbar, { DxItem } from "devextreme-vue/toolbar";

import SideNavMenu from "../components/side-nav-menu.vue";
import SmallToolbar from '../components/SmallToolbar.vue';
import MainPublic from './MainPublic.vue';
import AppFooter from './AppFooter.vue';


</script>

<template>
<MainPublic>
  <div class="outer">
    <div class="public-layout">
      <SmallToolbar/>
      <div class="m-8">
        <slot />

      </div>
    </div>
  </div>
</MainPublic>
</template>

<style lang="scss">
.side-nav-inner-toolbar {
  width: 60%;
}

.container {
  height: 100%;
  flex-direction: column;
  display: flex;
}

.layout-body {
  flex: 1;
  min-height: 0;
}

#navigation-header {
  @import "../themes/generated/variables.additional.scss";
  background-color: $base-accent;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);

  .menu-button .dx-icon {
    color: $base-text-color;
  }

  .screen-x-small & {
    padding-left: 20px;
  }

  .dx-theme-generic & {
    padding-top: 10px;
    padding-bottom: 10px;
  }
}

.public-layout{
  max-width: 1500px;
  margin-left: auto;
  margin-right: auto;
}
.outer{
  background-color: #f2f2f2;
  width: 100%;
  height: max-content;
}
</style>
