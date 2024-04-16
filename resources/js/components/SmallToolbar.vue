<template>
  <header class="header-component">
    <dx-toolbar class="header-toolbar">
      <dx-item
        location="before"
      >
        <div style="display:block;width:70px"></div>
      </dx-item>
      <dx-item
        location="before"
        css-class="header-title dx-toolbar-label"
      >
        <div><router-link to="/"><img src="img/logo.png" style="height:2em;"></router-link></div>
      </dx-item>

      <dx-item
        location="center"
      >
        <div style="font-size:1.5em;">Balkonsolar</div>
      </dx-item>

      <dx-item
        location="after"
        locate-in-menu="auto"
      >
      <template #default>
          <div>
            <dx-button
              class="user-button authorization"
              :width="210"
              height="100%"
              styling-mode="text"
            >
              <user-panel :email="null" :menu-items="[]" menu-mode="context" />
            </dx-button>
          </div>
        </template>
      </dx-item>
    </dx-toolbar>
  </header>
</template>

<script>
import DxButton from "devextreme-vue/button";
import DxToolbar, { DxItem } from "devextreme-vue/toolbar";
import auth from "../auth";
import { useRouter, useRoute } from 'vue-router';
import { computed, ref } from 'vue';
import { useStore } from '../store'

import UserPanel from "./user-panel.vue";
import notify from 'devextreme/ui/notify';

export default {
  components: {
    DxButton,
    DxToolbar,
    DxItem,
    UserPanel
  }
};
</script>

<style lang="scss">
@import "../themes/generated/variables.base.scss";
@import "../dx-styles.scss";

.header-component {
  flex: 0 0 auto;
  z-index: 1;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);

  .dx-toolbar .dx-toolbar-item.menu-button > .dx-toolbar-item-content .dx-icon {
    color: $base-accent;
  }
}

.dx-toolbar.header-toolbar .dx-toolbar-items-container .dx-toolbar-after {
  padding: 0 40px;

  .screen-x-small & {
    padding: 0 20px;
  }
}

.dx-toolbar .dx-toolbar-item.dx-toolbar-button.menu-button {
  width: $side-panel-min-width;
  text-align: center;
}

</style>
