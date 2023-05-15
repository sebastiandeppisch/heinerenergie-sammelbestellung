<template>
  <div style="max-width: 600px;">
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
      <DxItem>
        <SlideCard @forward="forward" :show-backward="false" :show-forward="false">
          <Sent v-model="r.advice"/>
        </SlideCard>
      </DxItem>
    </DxMultiView>
  </div>
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
import Sent from '../components/AdviceFormSlides/Sent.vue';

import { ref, reactive, computed, onMounted } from "vue";


const r = reactive({
  advice: {
    helpType_place: false,
    helpType_technical: false,
    helpType_bureaucracy: false,
    helpType_other: false,
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
  selectedSlide: 0
});

const multiView = ref(null);

function forward(){
  r.selectedSlide++;
}

function backward(){
  r.selectedSlide--;
}

function submit(){
  r.selectedSlide++;
}

</script>
<style scoped>

</style>