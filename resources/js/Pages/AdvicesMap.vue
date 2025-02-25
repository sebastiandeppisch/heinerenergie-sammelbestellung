<script setup lang="ts">

import { ref, onMounted, reactive, watch, computed} from "vue";
import {DxButton} from "devextreme-vue/button";
import "leaflet/dist/leaflet.css";

import {
  LMap,
  LTileLayer,
  LMarker,
  LPopup,
  LIcon,
  LControl,
  LLayerGroup,
  LControlLayers,
} from "@vue-leaflet/vue-leaflet";

import LaravelDataSource from "../LaravelDataSource";
//import 'leaflet/dist/leaflet.css';
import L from "leaflet";
import { latLng } from "leaflet";
import AdviceTypes from "../AdviceTypes";
import axios from "axios";
import { AdaptTableHeight } from "../helpers";
import { DxTextBox, DxButton as DxTextBoxButton } from 'devextreme-vue/text-box';
import notify from 'devextreme/ui/notify';
import { usePage, router } from "@inertiajs/vue3";
import {isAdmin, user as userRef} from '../authHelper';
import { LPolygon } from "@vue-leaflet/vue-leaflet";

const user = userRef.value;
const userId = user.id;

const emit = defineEmits(["selectAdviceId"])

const props = defineProps<{
  advices: App.Models.Advice[];
  advisors: App.Models.User[];
  groups: App.Data.GroupMapData[];
}>();
const advisors = props.advisors;


const advices = computed(() => {
  return props.advices;
});

const map = reactive({
  center: latLng(49.8728, 8.6512) as { lat: number; lng: number},
  zoom: 15,
});

const page = usePage();
const hash = window.location.hash;

if (hash !== '') {
  const parts = hash.replace('#', '').split('/');
  map.zoom = parseInt(parts[0]);
  map.center.lat = parseFloat(parts[1]);
  map.center.lng = parseFloat(parts[2]);
} else if (user.lat !== null && user.long !== null && user.lat !== undefined && user.long !== undefined) {
  map.center = latLng(user.lat, user.long);
}



function advisorName(advisorId: number){
  const advisor = advisors.find((advisor) => advisor.id === advisorId);
  if(advisor === undefined){
    return '';
  }
  return advisor.name;
}

function openAdvice(advice: App.Models.Advice){
  router.get('/advices/' + advice.id);
}


function ownId(){
  return user.id;
}

function addAdvice(advice: App.Models.Advice) {
  axios.post('api/advices/' + advice.id + '/assign').then(response => response.data).then((advice) => {
    router.visit('/advicesmap' + '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng);
  });
}

function userCanOpen(advice: App.Models.Advice){
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

function zoomChanged(e: number){
  map.zoom = e;
}

function centerChanged(e: { lat?: number; lng?: number }){
  if('lat' in e === false || 'lng' in e === false){
    return;
  }

  if(e.lat === undefined || e.lng === undefined){
    return;
  }

  map.center.lat = e.lat;
  map.center.lng = e.lng;
}


const outer = ref(null);
const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();

const search = ref('');
onMounted(() => {
  tableHeight.calcHeight();
});

watch(map, () => {
  window.location.hash = '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng;
});

function runSearch(){
  axios.get('api/map/search', {params: {query: search.value}}).then(response => response.data).then((data) => {
    if(data['lat'] === undefined || data['long'] === undefined){
      notify('Adresse nicht gefunden', 'error');
      return;
    }
    map.center = latLng(parseFloat(data.lat), parseFloat(data.long));
    map.zoom = 18; //a better approach would be to set the bounding box
  });
}

</script>

<template>
  <div ref="outer">
  <div :style="{height: reactiveHeight.height+170 + 'px', width: '100%'}">
    <LMap
      ref="map"
      :zoom="map.zoom"
      @update:zoom="zoomChanged"
      :center="[map.center.lat, map.center.lng]"
      @update:center="centerChanged"
      :minZoom="3"
      :maxZoom="18"
    >
      <LControlLayers
        :collapsed="false"
        :hide-single-base="true"
      />
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />
      <LControl
        position="topleft"
      >
        <div class="dx-card">
          <div style="display:flex;">
            <DxTextBox
              placeholder="Springe zu Adresse"
              v-model="search"
              value-change-event="keyup"
              @enterKey="runSearch"
            >
              <DxTextBoxButton
                :options="{
                  icon: 'search',
                  onClick: runSearch
                }"
                name="search"
                location="after"
              />
            </DxTextBox>
          </div>
        </div>
      </LControl>
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

            <div style="display:flex;flex-direction:column;margin:10px;">
              <div><b>Erledigte Beratungen</b></div>
              <div style="display:flex;flex-direction:row;justify-content:center;gap:15px;padding-top:5px;">
                <img src="/images/markers/gray_green.svg" style="height:3em;display:inline;" />
                <img src="/images/markers/gray_red.svg" style="height:3em;display:inline;" />
              </div>
            </div>
          
          </div>
        </div>
      </LControl>

      <LLayerGroup
        name="Beratungen"
        layer-type="overlay"
      >
        <LMarker
          v-for="advice in advices.filter(advice => advice.result < 2).filter(advice => advice.lat !== null && advice.long !== null)"
          :key="advice.id"
          :lat-lng="latLng(advice.lat ?? 0, advice.long ?? 0)"
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
                v-if="advice.can_edit"
              />
            </div>
          </LPopup>
        </LMarker>
      </LLayerGroup>
      <LLayerGroup
          name="Erledigte Beratungen"
          layer-type="overlay"
          :visible="false"
        >
        <LMarker
          v-for="advice in advices.filter(advice => advice.result >= 2).filter(advice => advice.lat !== null && advice.long !== null)"
          :key="advice.id"
          :lat-lng="latLng(advice.lat?? 0, advice.long ?? 0)"
        >
          <LIcon v-if="     advice.result === 2" icon-url="/images/markers/gray_green.svg" :icon-size="[50, 50]" />
          <LIcon v-else-if="advice.result === 3" icon-url="/images/markers/gray_red.svg" :icon-size="[50, 50]" />
          <LPopup>
            <div>
              <b>{{ advice.firstName }} {{ advice.lastName }}<br />
              {{ advice.street }} {{ advice.streetNumber}}<br />
              </b>
              <div v-if="advice.advisor_id !== null">Berater*in: {{ advisorName(advice.advisor_id )}}</div>
              <div v-if="advice.shares_ids.length > 0">Geteilt mit: {{ advice.shares_ids.map(advisorName).join(', ') }}</div>
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
      </LLayerGroup>
      <LLayerGroup
        name="Berater*innen"
        layer-type="overlay">
        <LMarker
          v-for="advisor in advisors.filter(advice => advice.lat !== null && advice.long !== null)"
          :key="advisor.id"
          :lat-lng="latLng(advisor.lat ?? 0, advisor.long?? 0)"
        >
          <LIcon icon-url="/images/markers/he_yellow.svg" :icon-size="[50, 50]" />
          <LPopup>
            <div>
              {{ advisor.name }}
            </div>
          </LPopup>
        </LMarker>
      </LLayerGroup>

      <LLayerGroup
        name="Initiativen"
        layer-type="overlay"
      >
        <LPolygon v-for="(item, index) in props.groups" :key="index" :lat-lngs="item.polygon" v-if="props.groups && props.groups.length > 0" />
        <LMarker v-for="(item, index) in props.groups" :key="index" :lat-lng="latLng(item.center.lat, item.center.long)" v-if="props.groups && props.groups.length > 0">
          <LIcon :icon-url="item.logo_path" :icon-size="[50, 50]" v-if="item.logo_path !== null" />
        </LMarker>
      </LLayerGroup>
    </LMap>
  </div>
</div>

</template>