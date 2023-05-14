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
      @click="emit('submit')"
    />
    <div style="height: 16px;display:block;"></div>
  </div>
</template>

<script setup lang="ts">
import DxTextArea from "devextreme-vue/text-area";
import DxButton from "devextreme-vue/button";
import { computed, reactive, ref } from "vue";

interface Props {
  modelValue: App.Models.Advice;
}

const props = defineProps<Props>();
const emit = defineEmits(["submit"]);

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue;
  },
  set(value) {

  },
});
</script>
