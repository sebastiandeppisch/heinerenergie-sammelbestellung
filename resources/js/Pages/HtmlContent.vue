<script setup lang="ts">
import PublicLayout from '../layouts/PublicLayout.vue';

const props = defineProps<{content: string}>();

defineOptions({
  layout: PublicLayout
});

</script>
<template>
  <div class="mt-8">
    <div class="dx-card content p-4">
      <div v-html="props.content">
      </div>
    </div>
  </div>
</template>