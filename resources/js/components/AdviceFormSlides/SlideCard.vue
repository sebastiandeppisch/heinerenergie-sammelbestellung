<template>
  <div style="width:100%;height;100%">
     <div class="dx-card slide-card">
          <div style="flex-grow: 1">
            <slot :allow-forward="allowForward"></slot>
          </div>
          <div style="display: flex">
            <DxButton
              text="Zurück"
              @click="backward"
              v-if="showBackward"
            />
            <div style="flex-grow: 1"></div>

            <DxButton
              text="Weiter"
              type="default"
              @click="forward"
              :disabled="!forwardEnabled"
              v-if="showForward"
            />
          </div>
        </div>
  </div>
</template>

<script setup lang="ts">
import DxButton from "devextreme-vue/button";
import { ref } from "vue";

defineProps({
  showBackward: {
    type: Boolean,
    default: true
  },
  showForward: {
    type: Boolean,
    default: true
  }
})

const forwardEnabled = ref(false);
const emit = defineEmits(["forward", "backward"])

function forward() {
  emit("forward");
}

function backward() {
  emit("backward");
}

function allowForward(allow: boolean){
  forwardEnabled.value = allow;
}


</script>
<style #scoped>
.slide-card {
  overflow: auto;
  white-space: normal;
  height: 400px;
  margin: 20px;
  padding: 20px;
  display: flex;
  flex-direction: column;
}
</style>