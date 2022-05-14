<template>
  <div class="user-panel">
    <div class="user-info">
      <div class="user-name">{{email}}</div>
    </div>

    <dx-context-menu
      v-if="menuMode === 'context'"
      target=".user-button"
      :items="menuItems"
      :width="210"
      show-event="dxclick"
      css-class="user-menu"
    >
      <dx-position my="top center" at="bottom center" />
    </dx-context-menu>

    <dx-list
      v-if="menuMode === 'list'"
      class="dx-toolbar-menu-action"
      :items="menuItems"
    />
  </div>
</template>

<script>
import DxContextMenu, { DxPosition } from "devextreme-vue/context-menu";
import DxList from "devextreme-vue/list";

export default {
  props: {
    menuMode: String,
    menuItems: Array,
    email: String
  },
  components: {
    DxContextMenu,
    DxPosition,
    DxList
  }
};
</script>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.user-info {
  display: flex;
  align-items: center;

  .dx-toolbar-menu-section & {
    padding: 10px 6px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  }

  .user-name {
    font-size: 14px;
    color: $base-text-color;
    margin: 0 9px;
  }
}

.user-panel {
  .dx-list-item .dx-icon {
    vertical-align: middle;
    color: $base-text-color;
    margin-right: 16px;
  }
  .dx-rtl .dx-list-item .dx-icon {
    margin-right: 0;
    margin-left: 16px;
  }
}

.dx-context-menu.user-menu.dx-menu-base {
  &.dx-rtl {
    .dx-submenu .dx-menu-items-container .dx-icon {
      margin-left: 16px;
    }
  }
  .dx-submenu .dx-menu-items-container .dx-icon {
    margin-right: 16px;
  }
  .dx-menu-item .dx-menu-item-content {
    padding: 3px 15px 4px;
  }
}

.dx-theme-generic .user-menu .dx-menu-item-content .dx-menu-item-text {
  padding-left: 4px;
  padding-right: 4px;
}
</style>
