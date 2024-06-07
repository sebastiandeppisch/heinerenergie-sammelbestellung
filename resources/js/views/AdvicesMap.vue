<script setup lang="ts">

import { ref, onMounted, reactive, defineEmits, watch} from "vue";
import {DxButton} from "devextreme-vue/button";
import "leaflet/dist/leaflet.css";

import {
  LMap,
  LTileLayer,
  LMarker,
  LPopup,
  LIcon,
  LControl,
} from "@vue-leaflet/vue-leaflet";

import LaravelDataSource from "../LaravelDataSource";
//import 'leaflet/dist/leaflet.css';
import L from "leaflet";
import { latLng } from "leaflet";
import { store } from "../store";
import AdviceTypes from "../AdviceTypes";
import axios from "axios";
import { AdaptTableHeight } from "../helpers";
import { useRouter, useRoute } from "vue-router";
const isAdmin = store.state.user.is_admin;

const emit = defineEmits(["selectAdviceId"])
const advice = ref(null);

const advicesDataSource = new LaravelDataSource("/api/advices");
const advices = ref([]);
const user = store.state.user;

const map = reactive({
  center: latLng(49.8728, 8.6512) as { lat: number; lng: number},
  zoom: 15,
});

const route = useRoute();
if(route.hash !== ''){
  const hash = route.hash.substr(1);
  const parts = hash.split('/');
  map.zoom = parseInt(parts[0]);
  map.center.lat = parseFloat(parts[1]);
  map.center.lng = parseFloat(parts[2]);
  console.log('hash', hash, parts, map);
}else{
  if(user.lat !== null && user.long !== null){
    map.center = latLng(user.lat, user.long);
  }
}

function loadAdvices(){
  advicesDataSource.load().then((data) => {
    advices.value = data.filter(
      (advice) => advice.lat !== null && advice.long !== null
    );
  });
}
loadAdvices();

advicesDataSource.store().on("updated", (data) => {
  console.log(data);
});

const advisorsDataSource = new LaravelDataSource("/api/users");
const advisors = ref([]);
advisorsDataSource.load().then((data) => {
  advisors.value = data.filter(
    (advisor) => advisor.lat !== null && advisor.long !== null
  );
});

function advisorName(advisorId){
  const advisor = advisors.value.find((advisor) => advisor.id === advisorId);
  if(advisor === undefined){
    return '';
  }
  return advisor.name;
}

function openAdvice(advice){
  emit('selectAdviceId', advice.id);
  console.log('open advice', advice.id);
}


function ownId(){
  return store.state.user.id;
}

function addAdvice(advice) {
  axios.post('api/advices/' + advice.id + '/assign').then(response => response.data).then((advice) => {
    advicesDataSource.store().push([{ type: "update", data: advice, key: advice.id }]);
  });
  advices.value = [];
  loadAdvices();
}

function userCanOpen(advice){
  const userId = store.state.user.id;
  if(isAdmin){
    return true;
  }
  if(advice.advisor_id === userId){
    return true;
  }
  if(advice.shares_ids.includes(userId)){
    return true;
  }
  return false;
}

const router = useRouter();

function zoomChanged(e){
  map.zoom = e;
}

function centerChanged(e){
  if('lat' in e === false || 'lng' in e === false){
    return;
  }
  map.center.lat = e.lat;
  map.center.lng = e.lng;
}


const outer = ref(null);
const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();
onMounted(() => {
  tableHeight.calcHeight();
});

watch(map, () => {
  router.push({name: 'advicesmap', hash: '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng});
})
</script>

<template>
  <div ref="outer">
  <div :style="{height: reactiveHeight.height+122 + 'px', width: '100%'}">
    <LMap
      ref="map"
      :zoom="map.zoom"
      @update:zoom="zoomChanged"
      :center="map.center"
      @update:center="centerChanged"
      :minZoom="3"
      :maxZoom="18"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />
      <LControl
        position="bottomleft"
      >
      <div class="dx-card" style="width:fit-content;">
        <div style="display:flex;flex-direction:row;">
          <div style="display:flex;flex-direction:column;margin:10px;">
            <div><b>Freie Beratungen</b></div>
            <div style="display:flex;flex-direction:row;justify-content:center;gap:15px;padding-top:5px;">
              <img src="/images/markers/house_magenta.svg" style="height:3em;display:inline;" />
              <img src="/images/markers/phone_magenta.svg" style="height:3em;display:inline;" />
            </div>
          </div>
          <div style="display:flex;flex-direction:column;margin:10px;">
            <div><b>Deine Beratungen</b></div>
            <div style="display:flex;flex-direction:row;justify-content:center;gap:15px;padding-top:5px;">
              <img src="/images/markers/house_green.svg" style="height:3em;display:inline;" />
              <img src="/images/markers/phone_green.svg" style="height:3em;display:inline;" />
            </div>
          </div>

          <div style="display:flex;flex-direction:column;margin:10px;">
            <div><b>Vergebene Beratungen</b></div>
            <div style="display:flex;flex-direction:row;justify-content:center;gap:15px;padding-top:5px;">
              <img src="/images/markers/house_blue.svg" style="height:3em;display:inline;" />
              <img src="/images/markers/phone_blue.svg" style="height:3em;display:inline;" />
            </div>
          </div>

          <div style="display:flex;flex-direction:column;margin:10px;">
            <div><b>Berater*innen</b></div>
            <div style="display:flex;flex-direction:row;justify-content:center;gap:15px;padding-top:5px;">
              <img src="/images/markers/he_yellow.svg" style="height:3em;display:inline;" />
            </div>
          </div>
          
          </div>
        </div>
      </LControl>

      <LMarker
        v-for="advice in advices"
        :key="advice.id"
        :lat-lng="latLng(advice.lat, advice.long)"
      >
        <LIcon v-if="     advice.advisor_id === null &&    advice.type === AdviceTypes.Home"    icon-url="/images/markers/house_magenta.svg" :icon-size="[50, 50]" />
        <LIcon v-else-if="advice.advisor_id === null &&    advice.type === AdviceTypes.Virtual" icon-url="/images/markers/phone_magenta.svg" :icon-size="[50, 50]" />
        <LIcon v-else-if="advice.advisor_id === ownId() && advice.type === AdviceTypes.Home"    icon-url="/images/markers/house_green.svg" :icon-size="[50, 50]" />
        <LIcon v-else-if="advice.advisor_id === ownId() && advice.type === AdviceTypes.Virtual" icon-url="/images/markers/phone_green.svg" :icon-size="[50, 50]" />
        <LIcon v-else-if="advice.advisor_id !== ownId() && advice.type === AdviceTypes.Home"    icon-url="/images/markers/house_blue.svg" :icon-size="[50, 50]" />
        <LIcon v-else-if="advice.advisor_id !== ownId() && advice.type === AdviceTypes.Virtual" icon-url="/images/markers/phone_blue.svg" :icon-size="[50, 50]" />
        <LPopup>
          <div>
            <b>{{ advice.firstName }} {{ advice.lastName }}<br />
            {{ advice.street }} {{ advice.streetNumber}}<br />
            </b>
            <div v-if="advice.advisor_id !== null">Berater*in: {{ advisorName(advice.advisor_id )}}</div>
            <div v-if="advice.shares_ids.length > 0">Geteilt mit: {{ advice.shares_ids.map(advisorName).join(', ') }}</div>
            <DxButton
              v-if="advice.advisor_id === null"
              type="default"
              text="Beratung übernehmen"
              @click="addAdvice(advice)"
              width="100%"
              style="margin-top:10px;"
            />
            <DxButton
              text="Beratung öffnen"
              @click="openAdvice(advice)"
              width="100%"
              style="margin-top:10px;"
              v-if="userCanOpen(advice)"
            />
          </div>
        </LPopup>
      </LMarker>
      <LMarker
        v-for="advisor in advisors"
        :key="advisor.id"
        :lat-lng="latLng(advisor.lat, advisor.long)"
      >
        <LIcon icon-url="/images/markers/he_yellow.svg" :icon-size="[50, 50]" />
        <LPopup>
          <div>
            {{ advisor.name }}
          </div>
        </LPopup>
      </LMarker>
    </LMap>
  </div>
</div>

</template>