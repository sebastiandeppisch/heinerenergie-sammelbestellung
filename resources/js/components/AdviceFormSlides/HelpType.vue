<script setup lang="ts">

import { computed } from "vue";

import DxCheckBox from "devextreme-vue/check-box";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import otherHelpTypeImage from '@/../img/heinerenergie-hochzeitsturm.svg';

type HelpTypeAdvice = Pick<App.Models.Advice,
  | 'helpType_place' | 'helpType_technical'
  | 'helpType_bureaucracy' | 'helpType_other'
  | 'firstName' | 'lastName'
>;

interface Props {
  modelValue: HelpTypeAdvice;
}

const props = defineProps<Props>();
const emit = defineEmits(["allowForward"]);

function checkForm() {
  if (advice.value.helpType_place || advice.value.helpType_bureaucracy || advice.value.helpType_technical || advice.value.helpType_other) {
    emit("allowForward", true);
  } else {
    emit("allowForward", false);
  }
}

const advice = computed<HelpTypeAdvice>({
  get() {
    return props.modelValue;
  },
  set(value) {
  }
});
</script>

<template>
  <div style="display: flex; flex-direction: column; gap:32px;">
    <span style="font-size: 1.2em">Bei was benötigst Du Beratung, {{ advice.firstName }}?</span>

    <div style="display: flex; flex-direction: row">
      <div style="width: 45px">
        <font-awesome-icon icon="fa fa-house" style="font-size: 2em" />
      </div>
      <div>
        <DxCheckBox
          v-model="advice.helpType_place"
          text="Ort (Balkon, Garten, Terrasse, etc.)"
          @value-changed="checkForm"
        />
        <div style="margin-left: 22px; margin-top: 3px">
          <a href="heinerenergie.de" target="_blank">Mehr Infos zur Aufstellung</a>
        </div>
      </div>
    </div>
    <div style="display: flex; flex-direction: row">
      <div style="width: 45px">
        <font-awesome-icon icon="fa fa-file-signature" style="font-size: 2em" />
      </div>
      <div>
        <DxCheckBox
          v-model="advice.helpType_bureaucracy"
          text="Bürokratie (Anmeldung, Förderung, etc.)"
          @value-changed="checkForm"
        />
        <div style="margin-left: 22px; margin-top: 3px">
          <a href="heinerenergie.de" target="_blank">Mehr Infos zum Papierkram</a
          >
        </div>
      </div>
    </div>
    <div style="display: flex; flex-direction: row">
      <div style="width: 45px">
        <font-awesome-icon icon="fa fa-wrench" style="font-size: 2em" />
      </div>
      <div>
        <DxCheckBox
          v-model="advice.helpType_technical"
          text="Technisches (Anschluss, Befestigung, etc.)"
          @value-changed="checkForm"
        />
        <div style="margin-left: 22px; margin-top: 3px">
          <a href="heinerenergie.de" target="_blank">Mehr Infos zur Technik</a>
        </div>
      </div>
    </div>
    <div style="display: flex; flex-direction: row">
      <div style="width: 45px">
        <!-- TODO fix path -->
        <img
          :src="otherHelpTypeImage"
          style="height: 2em"
        />
      </div>
      <div>
        <DxCheckBox
          v-model="advice.helpType_other"
          text="Sonstiges"
          @value-changed="checkForm"
        />
        <div style="margin-left: 22px; margin-top: 3px">
          <a href="heinerenergie.de" target="_blank">Unsere Webseite</a>
        </div>
      </div>
    </div>
  </div>
</template>
