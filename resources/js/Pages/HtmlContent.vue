<script setup lang="ts">
import PublicLayout from '../layouts/PublicLayout.vue';

const props = defineProps<{content: string}>();

defineOptions({
  layout: PublicLayout
});

</script>
<template>
  <div v-html="props.content">
  </div>
</template>