<template>
  <div style="display: flex; align-items: center;height:90%;flex-direction: column;">
    <div style="font-size: 1.5em;width:80%;margin-top:32px;">
      Vielen Dank für Deine Beratungsanfrage, {{ advice.firstName }}. Wir werden uns in Kürze bei Dir melden <font-awesome-icon icon="sun" style="color: "/> <br>
      
    </div>
    <div style="font-size: 1em;width:80%;margin-top:16px;">
        Du erhälst eine Kopie Deiner Anfrage per E-Mail. Falls Du keine Bestätigungsmail erhälst, schau bitte in Deinem Spam-Ordner nach oder melde Dich nochmal bei uns.
      </div>
    <div style="margin-top:32px">
      <font-awesome-icon icon="circle-check" size="6x" style="color: #00a651"/>
    </div>
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

const advice = computed<App.Models.Advice>({
  get() {
    return props.modelValue;
  },
  set(value) {

  },
});
</script>
