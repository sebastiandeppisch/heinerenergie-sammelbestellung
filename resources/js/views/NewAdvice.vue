<template>
  <DxMultiView
    height="440px"
    :swipe-enabled="false"
    ref="multiView"
    :selected-index="r.selectedSlide"
  >
    <DxItem>
      <SlideCard @forward="forward" @backward="backward" :show-backward="false" v-slot="scope">
        <Name v-model="r.advice" @allow-forward="scope.allowForward"/>
      </SlideCard>
    </DxItem>
    <DxItem>
      <SlideCard @forward="forward" @backward="backward" v-slot="scope">
        <HelpType v-model="r.advice" @allow-forward="scope.allowForward"/>
      </SlideCard>
    </DxItem>
    <DxItem>
      <SlideCard @forward="forward" @backward="backward" v-slot="scope">
        <Contact v-model="r.advice" @allow-forward="scope.allowForward"/>
      </SlideCard>
    </DxItem>
    <DxItem>
      <SlideCard @forward="forward" @backward="backward" v-slot="scope">
        <Place v-model="r.advice" @allow-forward="scope.allowForward"/>
      </SlideCard>
    </DxItem>
     <DxItem>
      <SlideCard @forward="forward" @backward="backward" v-slot="scope">
        <HouseType v-model="r.advice" @allow-forward="scope.allowForward"/>
      </SlideCard>
    </DxItem>
    <DxItem>
      <SlideCard @forward="forward" @backward="backward" v-slot="scope" :show-forward="false">
        <Submit v-model="r.advice" @allow-forward="scope.allowForward" @submit="submit"/>
      </SlideCard>
    </DxItem>
  </DxMultiView>
  {{r.advice}}
</template>

<script setup lang="ts">

import { DxMultiView, DxItem } from 'devextreme-vue/multi-view';
import SlideCard from '../components/AdviceFormSlides/SlideCard.vue';
import Name from '../components/AdviceFormSlides/Name.vue';
import HelpType from '../components/AdviceFormSlides/HelpType.vue';
import Contact from '../components/AdviceFormSlides/Contact.vue';
import Place from '../components/AdviceFormSlides/Place.vue';
import HouseType from '../components/AdviceFormSlides/HouseType.vue';
import Submit from '../components/AdviceFormSlides/Submit.vue';

import { ref, reactive, computed, onMounted } from "vue";
import axios from 'axios';

const r = reactive({
  advice: {
    helpType: {
      place: false,
      technical: false,
      bureaucracy: false,
      other: false,
    },
    houseType: null,
    other: false,
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    zip: "",
    city: "",
    street: "",
    streetNumber: "",
    placeNotes: "",
    landlordExists: null
  },
  contact: null,
  saving: false,
  selectedSlide: 5
});

const multiView = ref(null);

function forward(){
  r.selectedSlide++;
}

function backward(){
  r.selectedSlide--;
}

function submit(){
  axios.post('/api/newadvice', r.advice);
}

</script>
<style scoped>

</style>