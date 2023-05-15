<template>
  <div style="display: flex; flex-direction: column; height: 100%">
    <span style="font-size: 1.2em">Hast Du sonst noch Fragen oder möchtest ein Kommentar hinzufügen?</span>
    <DxTextArea
      v-model="advice.commentary"
      placeholder="Ich habe noch eine Frage zur Balkonhalterung..."
      height="calc(100% - 100px)"
    />
    <div style="height: 16px;display:block;"></div>
    <DxButton
      text="Beratungsanfrage abschicken"
      icon="fas fa-paper-plane"
      type="success"
      @click="submit"
      :disabled="loading"
    />
    <div style="align-items: center;display:flex; flex-direction: column;padding-top:8px;">
      <DxLoadIndicator
        :visible="loadIndicatorVisible"
        height="32px"
        width="32px"
      />
      <div style="display:block;height: 32px" v-if="!loadIndicatorVisible"></div>
    </div>
    <div style="height: 16px;display:block;"></div>
  </div>
</template>

<script setup lang="ts">
import DxTextArea from "devextreme-vue/text-area";
import DxButton from "devextreme-vue/button";
import { computed, reactive, ref } from "vue";
import { DxLoadIndicator } from 'devextreme-vue/load-indicator';
import axios from 'axios';
import notify from 'devextreme/ui/notify';

interface Props {
  modelValue: App.Models.Advice;
}

const props = defineProps<Props>();
const emit = defineEmits(["submit"]);

const loadIndicatorVisible = ref(false);

const loading = ref(false);

function submit() {
  loadIndicatorVisible.value = true;
  loading.value = true;
  const startTime = new Date().getTime();
  axios.post('/api/newadvice', advice.value).then((response) => {
    const endTime = new Date().getTime();
    const timeDiff = endTime - startTime;
    setTimeout(() => {
      emit('submit');
    }, Math.max(0, 1000 - timeDiff));
    console.log(response);
  }).catch((error) => {
    console.log(error);
    if('response' in error && 'data' in error.response && 'message' in error.response.data){
      notify(error.response.data.message, "error")
    }else{
      notify('error', "error");
    }
  }).finally(() => {
    const endTime = new Date().getTime();
    const timeDiff = endTime - startTime;
    setTimeout(() => {
      loadIndicatorVisible.value = false;
      loading.value = false;
    }, Math.max(0, 1000 - timeDiff));
  });
}

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue;
  },
  set(value) {

  },
});
</script>
