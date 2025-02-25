<script setup lang="ts">
import { ref } from 'vue';
import { user } from '../authHelper';
import AdviceForm from '../components/AdviceForm.vue';
import AdviceSharing from '../components/AdviceSharing.vue';
import AdviceActions from '../components/AdviceActions.vue';
import AdviceOrderActions from '../components/AdviceOrderActions.vue';
import AdviceTimeline from '../components/AdviceTimeline.vue';
import AdviceDetails from '../components/AdviceDetails.vue';

type Advice = App.Models.Advice;
type AdviceEvent = App.Data.AdviceEventData;

const props = defineProps<{
  advice: Advice;
  events: AdviceEvent[];
}>();

const sharedIds = ref(props.advice.shares_ids || []);
const advisor = user.value;
</script>

<template>
  <div style="padding:20px;">
    <h2>Beratung</h2>
    <div style="display:flex;flex-direction:row;gap:32px;">
      <div style="display:flex;flex-direction:column;gap:20px;">
        <AdviceForm :advice="advice" />
        <AdviceSharing 
          :advice-id="advice.id" 
          v-model:shared-ids="sharedIds" 
        />
        <AdviceActions 
          :advice="advice" 
          :advisor="advisor" 
        />
        <AdviceOrderActions 
          :advice="advice" 
          :advisor="advisor" 
        />
      </div>
      <div style="display:flex;flex-direction:column;gap:32px;">
        <AdviceTimeline :events="events" />
        <AdviceDetails :advice="advice" />
      </div>
    </div>
  </div>
</template>