<script setup lang="ts">


const props = defineProps<{
	event: App.Data.AdviceEventData
}>();

function formatDate(date: string): string {
  return new Date(date).toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

const isSystem = !props.event.user_name;
</script>

<template>
  <div class="timeline-item" :class="{ 'timeline-item-system': isSystem }">
    <div v-if="!isSystem" class="timeline-avatar">
      {{ props.event.initials }}
    </div>
    <div class="timeline-content" :class="{ 'timeline-content-system': isSystem }">
      <div class="timeline-header">
        <span class="timeline-author">{{ props.event.user_name || 'System' }}</span>
        <span class="timeline-date">{{ formatDate(props.event.created_at) }}</span>
      </div>
      <div class="timeline-message">
        {{ props.event.description }}
      </div>
      <div v-if="props.event.comment" class="timeline-comment">
        {{ props.event.comment }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.timeline-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
}

.timeline-item-system {
  padding-left: 48px;
}

.timeline-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary-color, #2196F3);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  flex-shrink: 0;
  font-size: 0.85em;
}

.timeline-content {
  flex: 1;
  background: #f8f9fa;
  border-radius: 12px;
  padding: 12px;
  position: relative;
}

.timeline-content-system {
  background: #EEEEEE;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.timeline-author {
  font-weight: 500;
  color: #333;
}

.timeline-date {
  font-size: 0.85em;
  color: #666;
}

.timeline-message {
  color: #333;
  line-height: 1.4;
}

.timeline-comment {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  color: #666;
  font-size: 0.95em;
}

.timeline-content::before {
  content: '';
  position: absolute;
  left: -6px;
  top: 12px;
  width: 0;
  height: 0;
  border-top: 6px solid transparent;
  border-bottom: 6px solid transparent;
  border-right: 6px solid #f8f9fa;
}

.timeline-content-system::before {
  display: none;
}
</style> 