<script setup lang="ts">
import TimelineItem from './TimelineItem.vue';

type AdviceEvent = App.Data.AdviceEventData;

const props = defineProps<{
  events: AdviceEvent[];
}>();
</script>

<template>
  <div class="dx-card timeline-card">
    <h3>Timeline</h3>
    <div class="timeline-list">
      <TimelineItem
        v-for="event in events"
			:key="event.id"
			:event="event"
      />
    </div>
  </div>
</template>

<style scoped>
.timeline-card {
  padding: 20px;
  max-width: 600px;
}

.timeline-list {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style> 