<script setup lang="ts">
import DxTreeView from "devextreme-vue/ui/tree-view";
import { sizes } from '../utils/media-query';
import navigation from '../app-navigation';
import { onMounted, ref, watch, computed } from 'vue';
import { store } from "../store";
import { Link, usePage } from "@inertiajs/vue3";
import { Inertia } from "@inertiajs/inertia";
import AppFooter from "./AppFooter.vue";

const isAdmin = computed(() => {
  if(store.state.user !== null){
    return store.state.user.is_admin;
  }
  Inertia.visit('/login');
});

const props = defineProps<{
  compactMode: boolean;
  isLargeScreen: boolean;
}>();

const path = computed(() => usePage().url);

const items = computed(() => {
  return navigation.filter((item) => 
    item.admin === false || isAdmin
  ).map((item) => {
    if(item.path && !(/^\//.test(item.path))){ 
      item.path = `/${item.path}`;
    }
    const expanded = false;
    return {...item, expanded} 
  })
});

const treeViewRef = ref();

function handleItemClick(e: any) {
  if (!e.itemData.path || props.compactMode) {
    return;
  }
  Inertia.visit(e.itemData.path);

  const pointerEvent = e.event;
  pointerEvent.stopPropagation();
}

function updateSelection () {
  if (!treeViewRef.value || !treeViewRef.value.instance) {
    return;
  }
  treeViewRef.value.instance.selectItem(path.value);
  treeViewRef.value.instance.expandItem(path.value);
}

onMounted(() => { 
  updateSelection();
  if (props.compactMode) {
    treeViewRef.value.instance.collapseAll();
  }

});



watch(
  () => path.value,
  () => {
    updateSelection();
  }
);

watch(
  () => props.compactMode,
  () => {
    if (props.compactMode) {
      treeViewRef.value.instance.collapseAll();
    } else {
      updateSelection();
    }
  }
);
</script>

<template>
  <div
    class="dx-swatch-additional side-navigation-menu"
  >
    <slot />
    <div class="menu-container">
      <dx-tree-view
        ref="treeViewRef"
        :items="items"
        key-expr="path"
        selection-mode="single"
        :focus-state-enabled="false"
        expand-event="click"
        @item-click="handleItemClick"
        width="100%"
      />
      <div>

        <footer style="margin-left:60px;">
          <p><a href="https://github.com/sebastiandeppisch/heinerenergie-sammelbestellung" ><img src="img/github.svg" alt="Github" style="height:1em;"></a></p>
          <p><Link href="/impress" style="color:white !important;text-decoration: none !important;">Impressum</Link></p>
          <p><Link href="/datapolicy" style="color:white !important;text-decoration: none !important;">Datenschutzerklärung</Link></p>
        </footer>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@import "../dx-styles.scss";
@import "../themes/generated/variables.additional.scss";

.side-navigation-menu {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  height: 100%;
  width: 250px !important;

  .menu-container {
    min-height: 100%;
    display: flex;
    flex: 1;
    flex-direction: column;

    .dx-treeview {
      // ## Long text positioning
      white-space: nowrap;
      // ##

      // ## Icon width customization
      .dx-treeview-item {
        padding-left: 0;
        padding-right: 0;

        .dx-icon {
          width: $side-panel-min-width !important;
          margin: 0 !important;
        }
      }
      // ##

      // ## Arrow customization
      .dx-treeview-node {
        padding: 0 0 !important;
      }

      .dx-treeview-toggle-item-visibility {
        right: 10px;
        left: auto;
      }

      .dx-rtl .dx-treeview-toggle-item-visibility {
        left: 10px;
        right: auto;
      }
      // ##

      // ## Item levels customization
      .dx-treeview-node {
        &[aria-level="1"] {
          font-weight: bold;
          border-bottom: 1px solid $base-border-color;
        }

        &[aria-level="2"] .dx-treeview-item-content {
          font-weight: normal;
          padding: 0 $side-panel-min-width;
        }
      }
      // ##
    }

    // ## Selected & Focused items customization
    .dx-treeview {
      .dx-treeview-node-container {
        .dx-treeview-node {
          &.dx-state-selected:not(.dx-state-focused) > .dx-treeview-item {
            background: transparent;
          }

          &.dx-state-selected > .dx-treeview-item * {
            color: $base-accent;
          }

          &:not(.dx-state-focused) > .dx-treeview-item.dx-state-hover {
            background-color: lighten($base-bg, 4);
          }
        }
      }
    }

    .dx-theme-generic .dx-treeview {
      .dx-treeview-node-container
        .dx-treeview-node.dx-state-selected.dx-state-focused
        > .dx-treeview-item
        * {
        color: inherit;
      }
    }
    // ##
  }
}
</style>
