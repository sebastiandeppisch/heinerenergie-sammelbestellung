<script setup lang="ts">
import DxTagBox from 'devextreme-vue/tag-box';
import LaravelDataSource from "../LaravelDataSource";
import notify from 'devextreme/ui/notify';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
  adviceId: number
  sharedIds: number[]
}>();

const emit = defineEmits<{
  (e: 'update:sharedIds', value: number[]): void;
}>();

const advisors = new LaravelDataSource('/api/users');

function updateAdvisors(e: { value: number[] }) {
  axios.post('/api/advices/' + props.adviceId + '/advisors', {advisors: e.value})
    .then(() => {
      notify('Teilung aktualisiert', 'success', 2000);
      router.reload();
  });
}
</script>

<template>
  <div class="sharing-container">
    <DxTagBox
      :data-source="advisors"
      display-expr="name"
      value-expr="id"
      :on-value-changed="updateAdvisors"
      label="Teilen mit"
      :value="sharedIds"
    />
    
    <div class="sharing-info">
      <div class="info-icon">
        <font-awesome-icon icon="fa fa-info-circle" />
      </div>
      <p class="info-text">
        Du kannst diese Beratung mit anderen Berater*innen teilen, um die Beratung gemeinsam durchzuführen
      </p>
    </div>
  </div>
</template>

<style scoped>
.sharing-container {
  padding: 0 24px 24px;
  padding-top: 16px;
}

.advisor-select {
  margin-bottom: 16px;
}

.advisor-name {
  color: #3498db;
  font-size: 14px;
  font-weight: 500;
}

.sharing-info {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  background: #f8f9fa;
}

.info-icon {
  color: #3498db;
  font-size: 16px;
  margin-top: 2px;
}

.info-text {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: #6c757d;
}
</style>