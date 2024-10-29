<script setup lang="ts">
import DxScrollView from "devextreme-vue/scroll-view";
import MainPublic from "./MainPublic.vue";
import { Link } from "@inertiajs/vue3";

const { title, description} = defineProps<{
  title: string;
  description?: string;
}>();

</script>

<template>
<div class="single-card">
  <div class="inner">
    <div class="dx-card">
      <div class="header">
        <div class="title">{{title}}</div>
        <div class="description">{{description}}</div>
      </div>
      <slot />
      
    </div>
    <Link class="back-link" href="/"><i class="dx-icon-back"></i> Zurück</Link>
  </div>
</div>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.inner{
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  flex-direction: column;
}

.back-link{
  margin-top: 20px;
  font-size: 14px;
  display: inline-block;
  cursor: pointer;
  text-decoration: none;
}

.single-card {
  width: 100%;
  height: 100%;

  .dx-card {
    width: 330px;
    padding: 40px;
    flex-grow: 0;

    .screen-x-small & {
      width: 100%;
      height: 100%;
      border-radius: 0;
      box-shadow: none;
      margin: 0;
      border: 0;
      flex-grow: 1;
    }

    .header {
      margin-bottom: 30px;

      .title {
        color: $base-text-color;
        line-height: 28px;
        font-weight: 500;
        font-size: 24px;
      }

      .description {
        color: rgba($base-text-color, alpha($base-text-color) * 0.7);
        line-height: 18px;
      }
    }
  }
}
</style>
