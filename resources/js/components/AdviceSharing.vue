<script setup lang="ts">
import DxTagBox from 'devextreme-vue/tag-box';
import LaravelDataSource from "../LaravelDataSource";
import notify from 'devextreme/ui/notify';
import axios from 'axios';

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
      emit('update:sharedIds', e.value);
  });
}
</script>

<template>
  <div class="dx-card" style="max-width:600px;padding:20px;">
    <DxTagBox
      :data-source="advisors"
      display-expr="name"
      value-expr="id"
      :on-value-changed="updateAdvisors"
      label="Teilen mit"
      :value="sharedIds"
    />
    <div style="padding-top:5px;opacity:0.5;">
      Du kannst diese Beratung mit anderen Berater*innen teilen, um die Beratung gemeinsam duchzuführen
    </div>
  </div>
</template>