<script setup lang="ts">
import DxDrawer from "devextreme-vue/drawer";
import DxScrollView from "devextreme-vue/scroll-view";

import HeaderToolbar from "@/layouts/HeaderToolbar.vue";
import SideNavMenu from "@/layouts/SideNavMenu.vue";
import { computed, ref, watch, reactive} from 'vue';
import { store } from '../store'
import { usePage } from "@inertiajs/vue3";

import {getScreenSizeInfo} from '../utils/media-query';

import AppFooter from "@/layouts/AppFooter.vue";
import { onMounted } from 'vue';

const screen = reactive({ getScreenSizeInfo: {} as { isXSmall: boolean; isLarge: boolean; cssClasses: string[] } });
screen.getScreenSizeInfo = getScreenSizeInfo();

const isXSmall = computed(() => screen.getScreenSizeInfo.isXSmall);
const isLarge = computed(() => screen.getScreenSizeInfo.isLarge);

const props = defineProps<{
  title?: string|undefined;
}>();

const path = computed(() => usePage().url);


const scrollViewRef = ref();
const menuOpened = ref(isLarge.value);
const menuTemporaryOpened = ref(false);

function toggleMenu(e) {
  const pointerEvent = e.event;
  pointerEvent.stopPropagation();
  if (menuOpened.value) {
    menuTemporaryOpened.value = false;
  }
  menuOpened.value = !menuOpened.value;
}

function handleSideBarClick() {
  if (menuOpened.value === false) {
    menuTemporaryOpened.value = true;
  }
  menuOpened.value = true;
}

const drawerOptions = computed(() => {
  const shaderEnabled = !isLarge.value;
  return {
    menuMode: isLarge.value ? "shrink" : "overlap",
    menuRevealMode: isXSmall.value ? "slide" : "expand",
    minMenuSize: isXSmall.value ? 0 : 60,
    maxMenuSize: isXSmall.value ? 250 : undefined,
    closeOnOutsideClick: shaderEnabled,
    shaderEnabled
  };
});

watch(
  () => isLarge.value,
  () => {
    console.log("watch isLarge", isLarge.value);
    if (!menuTemporaryOpened.value) {
      menuOpened.value = isLarge.value;
    }
});


watch(
  () => path.value,
  () => {
    if (menuTemporaryOpened.value || !isLarge.value) {
      menuOpened.value = false;
      menuTemporaryOpened.value = false;
    }
  scrollViewRef.value.instance.scrollTo(0);
  }
);



const cssClasses = computed(() => {
  return ["app"].concat(screen.getScreenSizeInfo.cssClasses);
});

onMounted(() => {
  window.addEventListener("resize", () => {
    screen.getScreenSizeInfo = getScreenSizeInfo();
  });
});

</script>

<template>
  <div id="root">
    <div :class="cssClasses">
      <div class="side-nav-outer-toolbar">
        <header-toolbar
          class="layout-header"
          :menu-toggle-enabled="true"
          :toggle-menu-func="toggleMenu"
          :title="props.title"
        />
      <dx-drawer
          class="layout-body"
          position="before"
          template="menuTemplate"
          v-model:opened="menuOpened"
          :opened-state-mode="drawerOptions.menuMode"
          :reveal-mode="drawerOptions.menuRevealMode"
          :min-size="drawerOptions.minMenuSize"
          :max-size="drawerOptions.maxMenuSize"
          :shading="drawerOptions.shaderEnabled"
          :close-on-outside-click="drawerOptions.closeOnOutsideClick"
        >
          <dx-scroll-view ref="scrollViewRef" class="with-footer">
            <div class="content">
              <slot></slot>
            </div>
            <app-footer />
          </dx-scroll-view>
          <template #menuTemplate>
            <side-nav-menu
              :compact-mode="!menuOpened"
              :is-large-screen="isLarge"
              @click="handleSideBarClick"
            />
          </template>
        </dx-drawer>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.side-nav-outer-toolbar {
  flex-direction: column;
  display: flex;
  height: 100%;
  width: 100%;
}

.layout-header {
  z-index: 1501;
}

.layout-body {
  flex: 1;
  min-height: 0;
}

.content {
  flex-grow: 1;
}

html,
body {
  margin: 0px;
  min-height: 100%;
  height: 100%;
}

#root {
  height: 100%;
}

* {
  box-sizing: border-box;
}

.app {
  @import "./../themes/generated/variables.base.scss";
  background-color: darken($base-bg, 5);
  display: flex;
  height: 100%;
  width: 100%;
}
</style>
