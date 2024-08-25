<script setup lang="ts">
import { subscribe, unsubscribe, getScreenSizeInfo} from "../utils/media-query";
import {
  reactive,
  onMounted,
  onBeforeUnmount,
  computed
} from "vue";
import AppFooter from "./AppFooter.vue";


const screen = reactive({ getScreenSizeInfo: {} as { isXSmall: boolean; isLarge: boolean; cssClasses: string[] } });
screen.getScreenSizeInfo = getScreenSizeInfo();


onMounted(() => {
  window.addEventListener("resize", () => {
    screen.getScreenSizeInfo = getScreenSizeInfo();
  });
});

const cssClasses = computed(() => {
  return ["app"].concat(screen.getScreenSizeInfo.cssClasses);
});
</script>

<template>
  <div id="root">
    <div :class="cssClasses">
      <div class="layout">
        <div class="content">
          <slot></slot>
        </div>
        <AppFooter />
      </div>
    </div>
  </div>
</template>

<style lang="scss">
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
  @import "../themes/generated/variables.base.scss";
  background-color: darken($base-bg, 5);
  display: flex;
  height: 100%;
  width: 100%;
}

.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-width: 100vw;
}

.content {
  flex-grow: 1;
  flex-direction: column;
}


</style>
