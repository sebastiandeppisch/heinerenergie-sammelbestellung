<script setup lang="ts">
import TimelineItem from './TimelineItem.vue';
import { DxTextArea, DxButton } from 'devextreme-vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route} from 'ziggy-js';

type AdviceEvent = App.Data.AdviceEventData;

const props = defineProps<{
  events: AdviceEvent[];
  adviceId: string;
}>();

const form = useForm({
  comment: ''
});

const submitComment = () => {
  if (!form.comment.trim()) return;

  form.post(route('advices.comment.store', { advice: props.adviceId }), {
    onSuccess: () => {
      form.reset();
    }
  });
};
</script>

<template>
  <div class="timeline-card">
    <div class="timeline-list">
      <TimelineItem
        v-for="event in events"
			:key="event.id"
			:event="event"
      />
    </div>

    <div class="new-comment-container">
      <h4>Neue Notiz hinzufügen</h4>
      <form @submit.prevent="submitComment">
        <DxTextArea
          v-model="form.comment"
          :height="100"
          placeholder="Neue Notiz..."
          class="comment-textarea"
          :disabled="form.processing"
          value-change-event="keyup"
        />
        <DxButton
          text="Notiz hinzufügen"
          type="default"
          class="submit-comment-btn"
          :disabled="form.processing || !form.comment.trim()"
          @click="submitComment"
        />
      </form>
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

.new-comment-container {
  margin-top: 20px;
  border-top: 1px solid #e0e0e0;
  padding-top: 15px;
}

.comment-textarea {
  margin-bottom: 10px;
  width: 100%;
}

.submit-comment-btn {
  margin-top: 10px;
}
</style>
