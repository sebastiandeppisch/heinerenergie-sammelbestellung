<script setup lang="ts">
const props = defineProps<{
  advisorInfo: string
}>();

</script>

<template>
  <div ref="outer">
    <h2 class="content-block">Dashboard</h2>
    <p class="dx-card content" style="padding:16px;margin:32px;" >
      <h2>Berater*innen-Infos</h2>
      <div v-html="props.advisorInfo">
      </div>
    </p>
  </div>
</template>