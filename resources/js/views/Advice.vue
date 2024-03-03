<template>
  <div v-if="adviceId !==null">
    <div v-if="advice !== null" style="padding:20px;">
      <h2>Beratung Test</h2>
      <div style="display:flex;flex-direction:row;gap:32px;">
        <div>
          <div class="dx-card" style="max-width:600px;padding:20px;">
            <DxForm
              label-mode="floating"
              :col-count="2"
              :form-data="advice"
            >
              <DxGroupItem
                caption="Name"
              >
                <DxItem data-field="firstName" :label="{ text: 'Vorname'}" />
                <DxItem data-field="lastName" :label="{ text: 'Nachname'}"/>
              </DxGroupItem>

              <DxGroupItem
                caption="Kontakt"
              >
                <DxItem data-field="phone" :label="{ text: 'Telefonnummer'}"/>
                <DxItem data-field="email" :label="{ text: 'E-Mail Adresse'}"/>
              </DxGroupItem>

              <DxGroupItem
                caption="Adresse"
              >
                <DxItem data-field="street" :label="{ text: 'Straße'}"/>
                <DxItem data-field="streetNumber" :label="{ text: 'Hausnummer'}"/>
                <DxItem data-field="zip" :label="{ text: 'Postleitzahl'}"/>
                <DxItem data-field="city" :label="{ text: 'Darmstadt'}"/>
              </DxGroupItem>

              <DxGroupItem
                caption="Beratung"
              >
                <DxItem
                  data-field="advice_status_id"
                  :label="{ text: 'Status'}"
                  editor-type="dxSelectBox"
                  :editor-options="{
                    dataSource: adviceStatus,
                    displayExpr: 'name',
                    valueExpr: 'id' }"
                />
                <DxItem
                  data-field="type"
                  :label="{ text: 'Typ'}"
                  editor-type="dxRadioGroup"
                  :editor-options="{
                    dataSource: adviceTypes,
                    displayExpr: 'name',
                    valueExpr: 'id',
                    layout: 'horizontal',
                    itemTemplate: radioBoxLayout
                  }"
                />
                <DxItem data-field="commentary" :label="{ text: 'Kommentar'}" editor-type="dxTextArea" :editor-options="{ autoResizeEnabled: true}"/>
              </DxGroupItem>
              
              <DxButtonItem
                :button-options="{ text: 'Speichern', type: 'success', useSubmitBehavior: true, width: '100%', onClick: onSubmit }"
                :col-span="2"
              />
            </DxForm>
          </div>
          <div class="dx-card" style="max-width:600px;padding:20px;margin-top:20px;">
            <DxTagBox
              :data-source="advisors"
              display-expr="name"
              value-expr="id"
              :on-value-changed="updateAdvisors"
              label="Teilen mit"
              v-model="sharedIds"
            />
            <div style="padding-top:5px;opacity:0.5;">Du kannst diese Beratung mit anderen Berater*innen teilen, um die Beratung gemeinsam duchzuführen</div>
          </div>
          <div style="margin-top:20px;display:flex;gap:20px;">
            <DxDropDownButton
              :items="navigationTypes"
              icon="map"
              text="Navigation öffnen"
              @item-click="openNavigation"
              display-expr="name"
              key-expr="id"
            />
            <a :href="phoneLink"><DxButton text="Anrufen" icon="tel" /></a>
            <a :href="mailLink"><DxButton text="E-Mail verfassen" icon="email" /></a>
          </div>
          <div style="margin-top:20px;display:flex;gap:20px;">
            <a :href="orderMailLink"><DxButton text="E-Mail mit Bestelllink verfassen" icon="email" /></a>
            <DxButton
              text="Bestelllink kopieren"
              icon="copy"
              @click="copyOrderLink"
            />
          </div>
        </div>
        <div>
          <div class="dx-card" style="padding:16px;display: flex;flex-direction: column;gap:16px;">
            <div>
              <b>Benötigt Hilfe bei:</b>
              <div v-if="advice.helpType_place">
                <font-awesome-icon icon="fa fa-house"  />
                <span> Ort (Balkon, Garten, Terrasse, etc.)</span>
              </div>
              <div v-if="advice.helpType_bureaucracy">
                <font-awesome-icon icon="fa fa-file-signature" />
                <span> Bürokratie (Anmeldung, Förderung, etc.)</span>
              </div>
              <div v-if="advice.helpType_technical">
                <font-awesome-icon icon="fa fa-wrench" />
                <span> Technisches (Anschluss, Befestigung, etc.)</span>
              </div>
              <div v-if="advice.helpType_other">
                <img
                  src="https://balkon.heinerenergie.de/images/heinerenergie-hochzeitsturm.svg"
                  style="height: 2em"
                />
                <span>Andere Themen</span>
              </div>

              <div v-if="!advice.helpType_place && !advice.helpType_bureaucracy && !advice.helpType_technical && !advice.helpType_other">
                <span><i>Keine Angabe</i></span>
              </div>
            </div>
            <div>
              <b>Gebäudeart:</b>
              <div v-if="advice.houseType === 0">
                <font-awesome-icon icon="fa fa-home" />
                <span>Einfamilienhaus</span>
              </div>
              <div v-if="advice.houseType === 1">
                <font-awesome-icon icon="fa fa-building" />
                <span>Mehrfamilienhaus</span>
              </div>
              <div v-else>
                <span><i>Keine Angabe/Sonstiges</i></span>
              </div>
              <b>Vermieter*in / WEG vorhanden?</b> <div v-if="advice.landlordExists">Ja</div><div v-else>Nein</div> 
            </div>
            <div>
              <b>Wo soll das Steckersolargerät installiert werden?</b>
              <div v-if="advice.placeNotes !== null && advice.placeNotes.length > 0"> {{ advice.placeNotes }}</div>
              <div v-else><i>Keine Angabe</i></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else style="height:100%;width:100%;min-height:300px" id="advice-loading" >
      <DxLoadPanel
        :visible="true"
        :show-indicator="true"
        :show-pane="true"
        :position="{ of: '#advice-loading' }"
      />
    </div>
  </div>
  <div v-else>
    <h1>Keine Beratung ausgewählt</h1>
    <DxRadioGroup
      :items="[{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}]"
      displayExpr="name"
      valueExpr="id"
      v-if="false"
    />
  </div>
</template>

<script setup lang="ts">
import DxTextArea from 'devextreme-vue/text-area';
import DxRadioGroup from 'devextreme-vue/radio-group';
import { DxForm, DxItem, DxSimpleItem, DxGroupItem, DxButtonItem} from 'devextreme-vue/form';
import { DxLoadPanel } from 'devextreme-vue/load-panel';
import { ref, onMounted, reactive, defineProps, PropType, watch, computed } from "vue";
import LaravelDataSource from "../LaravelDataSource";
import notify from 'devextreme/ui/notify';
import axios from 'axios';
import DxTagBox from 'devextreme-vue/tag-box';
import DxButton from 'devextreme-vue/button';
import { store } from '../store';
import DxDropDownButton from 'devextreme-vue/drop-down-button';
type Advice = App.Models.Advice;

const advice = ref(null as Advice | null);

const advicesDataSource = new LaravelDataSource('/api/advices');

const props = defineProps({
  adviceId: {
    type: [Number, null],
    required: true,
    default: null
  },
});

const adviceStatus = new LaravelDataSource('/api/advicestatus');
const adviceTypes = new LaravelDataSource('/api/advicetypes');
const advisors = new LaravelDataSource('/api/users');

const sharedIds = ref([] as number[]);

const advisor = store.state.user;

const navigationTypes = [
  { id: 'google', name: 'Google Maps' },
  { id: 'apple', name: 'Apple Maps' },
  { id: 'osm', name: 'Open Streep Maps' },
];

watch(props, (newVal, oldVal) => {
    console.log("watch adviceId", newVal.adviceId, oldVal.adviceId);
    if (newVal.adviceId !== null) {
      fetchAdvice(newVal.adviceId);
    }
  }
);

function radioBoxLayout(data: any) {
  const icons = {
    Home: 'home',
    Virtual: 'tel',
    DirectOrder: 'cart'
  }

  const helpText = {
    Home: 'Beratung vor Ort',
    Virtual: 'Beratung per Telefon',
    DirectOrder: 'Direktbestellung'
  }

  return `<i style="font-size:1.5em;" class="dx-icon-${icons[data.name]}" title='${helpText[data.name]}'></i>`;
};

function fetchAdvice(id: number) {
  advice.value = null;
  advicesDataSource
    .load()
    .then((advices) => {
      advices.forEach((a: Advice) => {
        if (a.id === id) {
          advice.value = a;
          sharedIds.value = a.shares_ids;
        }
      });
    });
}

function onSubmit(){
  advicesDataSource.store().update(props.adviceId, advice.value).then((result) => {
    notify("Beratung gespeichert", "success", 2000);
  }).catch((error) => {
    notify(error, "error", 2000);
  });
}

if(props.adviceId !== null){
  fetchAdvice(props.adviceId);
}

function updateAdvisors(e: any) {
  axios.post('api/advices/' + props.adviceId + '/advisors', {advisors: e.value})
    .then(() => {
      notify('Teilung aktualisiert', 'success', 2000);
  })
}

function sendOrderLink() {
  axios.post('/api/advices/' + props.adviceId + '/sendorderlink')
    .then(() => {
      notify('Bestelllink versendet', 'success', 2000);
    })
}

function openNavigation(e){
  const type = e.itemData.id;
  const address = advice.value?.street + ' ' + advice.value?.streetNumber + ', ' + advice.value?.zip + ' ' + advice.value?.city;
  console.log(type, address)
  switch(type){
    case 'google':
      window.open('https://www.google.com/maps/dir/?api=1&destination=' + address + '&travelmode=bicycling', '_blank');
      break;
    case 'apple':
      window.open('https://maps.apple.com/?daddr=' + address + '&dirflg=w', '_blank');
      break;
    case 'osm':
      window.open('https://www.openstreetmap.org/directions?engine=graphhopper_bicycle&route=' + advisor.lat + '%2C' + advisor.long + '%3B' + advice.value?.lat + '%2C' + advice.value?.long, '_blank');
      break;
  }
}

const mailLink = computed(() => {
  const body = 'Hallo ' + advice.value?.firstName + ',%0D%0A%0D%0A' + 'TEXT' + '%0D%0A%0D%0A' + 'Gruß,%0D%0A' + advisor.first_name;
  const subject = 'heiner*energie%20Beratung';
   
  return 'mailto:' + advice.value?.email + '?subject=' + subject + '&body=' + body
});

const orderLink = computed(() => {
  const data =  {
    advisorEmail: advisor.email,
    firstName: advice.value?.firstName,
    lastName: advice.value?.lastName,
    street: advice.value?.street,
    streetNumber: advice.value?.streetNumber,
    zip: advice.value?.zip,
    city: advice.value?.city,
    email: advice.value?.email,
    phone: advice.value?.phone,
    email_confirmation: advice.value?.email,
  }
  return 'https://balkon.heinerenergie.de/sammelbestellung?formdata=' + JSON.stringify(data);
})

const orderMailLink = computed(() => {
  const url = orderLink.value;
  const body = 'Hallo ' + advice.value?.firstName + ',%0D%0A%0D%0A' + url + '%0D%0A%0D%0A' + 'HIER MUSST DU NOCH DAS PASSWORT EINTRAGEN' + '%0D%0A%0D%0AGruß,%0D%0A' + advisor.first_name;
  const subject = 'heiner*energie%20Sammelbestellung';
   
  return 'mailto:' + advice.value?.email + '?subject=' + subject + '&body=' + body
});

function copyOrderLink() {
  const el = document.createElement('textarea');
  el.value = orderLink.value;
  document.body.appendChild(el);
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  notify('Bestelllink wurde in die Zwischenablage kopiert', 'success', 2000);
}

const phoneLink = computed(() => {
  return 'tel:' + advice.value?.phone;
});

</script>