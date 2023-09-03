<template>
  <div ref="outer">
    <h2 class="content-block">Beratungen</h2>
    <div class="main-table">
      <DxDataGrid
        class="dx-card wide-card"
        :data-source="advices"
        :show-borders="false"
        :column-auto-width="true"
        :column-hiding-enabled="true"
        :height="reactiveHeight.height"
        min-height="450px"
        :allow-column-reordering="true"
        :remote-operations="false"
      >
        <DxGroupPanel :visible="true" empty-panel-text="Spalten hierher ziehen, zum Gruppieren"/>
        <DxGrouping :auto-expand-all="r.autoExpand"/>
        <DxScrolling mode="virtual" />
        <DxFilterRow :visible="true" />
        <DxEditing
          :allow-updating="rowCanBeEdited"
          :allow-adding="false"
          :allow-deleting="false"
          mode="cell"
        />
        <DxToolbar>
          <DxItem
            location="before"
            template="test"
          />
          <DxItem
            location="before"
            name="groupPanel"
          />
          <DxItem
            location="before"
            template="autoexpand"
          />
          <DxItem
            location="after"
            template="newadvice"
          />
        </DxToolbar>
        <template #test>
          <DxButton
            icon="fields"
            text="Status editieren"
            @click="openStatus"
            v-if="isAdmin"
          />        
        </template>
        <template #newadvice>
          <DxButton
            icon="add"
            @click="openNewAdvice"
          />
        </template>
        <template #autoexpand>
          <div>
            <DxSwitch
              v-model:value="r.autoExpand"
            />
            <span style="position:relative;top:-8px;opacity:60%">
              Gruppen aufklappen
            </span>
          </div>
        </template>
        <DxColumn type="buttons" caption="Öffnen">
          <DxTableButton
            hint="Beratung öffnen"
            icon="user"
            text="Öffnen"
            @click="openAdvice"
            :visible="isOpenVisible"
          />
        </DxColumn>
        <DxColumn
          data-field="created_at"
          caption="Erstellt am"
          :allow-editing="false"
        />
        <DxColumn data-field="advice_status_id" caption="Status">
          <DxLookup
            :data-source="adviceStatus"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="advisor_id" caption="Berater*in" v-if="isAdmin" width="350px">
          <DxLookup
            :data-source="sortedAdvisors"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="advisor_id" caption="Berater*in" :allow-editing="false" cell-template="simpleadvisorassignment" v-if="!isAdmin">
          <DxLookup
            :data-source="sortedAdvisors"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="distance" caption="Luftlinie" cell-template="distance" :allow-editing="false"/>
        <DxColumn data-field="type" caption="Typ" cell-template="typeIcon">
          <DxLookup
            :data-source="adviceTypes"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="firstName"    caption="Vorname"         :allow-editing="false"/>
        <DxColumn data-field="lastName"     caption="Nachname"        :allow-editing="false" />
        <DxColumn data-field="email"        caption="E-Mail Adresse"  :allow-editing="false" />
        <DxColumn data-field="phone"        caption="Telefonnummer"   :allow-editing="false"/>
        <DxColumn data-field="street"       caption="Straße & Nr."          :allow-editing="false" cell-template="street"/>
        <DxColumn data-field="zip"          caption="Plz"             :allow-editing="false"/>
        <DxColumn data-field="city"         caption="Stadt"           :allow-editing="false"/>

        <DxSummary :recalculate-while-editing="true">
          <DxTotalItem column="advice_status_id" summary-type="count" />
          <DxGroupItem
            summary-type="count"
            display-format="{0} Beratungen"
          />
        </DxSummary>
        <template #distance="{data}">
          <PhysicalValue :value="data.data.distance" unit="m" />   
        </template>
        <template #street="{data}">
          <div>{{ data.data.street }}  {{ data.data.streetNumber }}</div>
        </template>
        <template #simpleadvisorassignment="{data}">
          <div v-if="data.data.advisor_id !== null">{{r.advisorNames.get(data.data.advisor_id)}}</div>
          <div v-else>
            <DxButton
              text="Übernehmen"
              @click="assignAdvice(data.data.id)"
              type="default"
            />
          </div>
        </template>
        <template #typeIcon="{data}">
          <div v-if="data.data.type === 0">    <i class="dx-icon dx-icon-home"></i></div>
          <div v-else-if="data.data.type === 1"> <i class="dx-icon dx-icon-tel"></i></div>
          <div v-else-if="data.data.type === 2"> <i class="dx-icon dx-icon-cart"></i></div>
          <div v-else>??</div>
        </template>
      </DxDataGrid>
      <DxPopup
        v-model:visible="r.popupVisible"
        title="Beratungsstatus"
        hide-on-outside-click="true"
        :show-close-button="true"
        width="400px"
        height="600px"
      >
        <AdviceStatus />
      </DxPopup>  

      <DxPopup
        v-model:visible="r.newAdvicePopupVisible"
        title="Neue Beratung"
        hide-on-outside-click="true"
        :show-close-button="true"
        max-width="900px"
      >
        <DxScrollView
          width="100%"
          height="100%"
        >
          <DxForm
            label-mode="floating"
            :col-count="2"
            :form-data="newadvice"
          >
            <DxFormGroupItem
              caption="Name"
            >
              <DxFormItem data-field="firstName" :label="{ text: 'Vorname'}" />
              <DxFormItem data-field="lastName" :label="{ text: 'Nachname'}"/>
            </DxFormGroupItem>

            <DxFormGroupItem
              caption="Kontakt"
            >
              <DxFormItem data-field="phone" :label="{ text: 'Telefonnummer'}"/>
              <DxFormItem data-field="email" :label="{ text: 'E-Mail Adresse'}"/>
            </DxFormGroupItem>

            <DxFormGroupItem
              caption="Adresse"
            >
              <DxFormItem data-field="street" :label="{ text: 'Straße'}"/>
              <DxFormItem data-field="streetNumber" :label="{ text: 'Hausnummer'}"/>
              <DxFormItem data-field="zip" :label="{ text: 'Postleitzahl'}"/>
              <DxFormItem data-field="city" :label="{ text: 'Stadt'}"/>
            </DxFormGroupItem>

            <DxFormGroupItem
              caption="Beratung"
            >
              <DxFormItem
                data-field="advice_status_id"
                :label="{ text: 'Status'}"
                editor-type="dxSelectBox"
                :editor-options="{
                  dataSource: adviceStatus,
                  displayExpr: 'name',
                  valueExpr: 'id' }"
              />
              <DxFormItem
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
              <DxFormItem data-field="commentary" :label="{ text: 'Kommentar'}" editor-type="dxTextArea" :editor-options="{ autoResizeEnabled: true}"/>
            </DxFormGroupItem>
            
            <DxButtonItem
              :button-options="{ text: 'Speichern', type: 'success', useSubmitBehavior: true, width: '100%', onClick: saveNewAdvice }"
              :col-span="2"
            />
          </DxForm>
        </DxScrollView>
      </DxPopup>
    </div>
  </div>
</template>

<script setup lang="ts">
import LaravelDataSource from "../LaravelDataSource";
import { AdaptTableHeight } from "../helpers";
import { ref, onMounted, reactive, defineEmits } from "vue";
import DxPopup from 'devextreme-vue/popup';
import AdviceStatus from "./AdviceStatus.vue";
import PhysicalValue from "./PhysicalValue.vue";
import Advice from "./Advice.vue";
import { DxScrollView } from 'devextreme-vue/scroll-view';

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxSummary,
  DxTotalItem,
  DxMasterDetail,
  DxToolbar,
  DxItem,
  DxLookup,
  DxFilterRow,
  DxScrolling,
  DxButton as DxTableButton,
  DxGroupPanel,
  DxGrouping,
  DxGroupItem,
} from "devextreme-vue/data-grid";
import LaravelLookupSource from "../LaravelLookupSource";
import DxButton from "devextreme-vue/button";
import {store} from "../store";
import axios from "axios";
import CustomStore from "devextreme/data/custom_store";
import { DxSwitch } from "devextreme-vue";
import { DxForm, DxItem as DxFormItem, DxSimpleItem, DxGroupItem as DxFormGroupItem, DxButtonItem} from 'devextreme-vue/form';
import notify from "devextreme/ui/notify";

const emit = defineEmits(["selectAdviceId"])

const advices = new LaravelDataSource("api/advices");
const advisors = new LaravelDataSource("api/users");
const adviceStatus = new LaravelLookupSource('api/advicestatus');
const adviceTypes = new LaravelLookupSource('api/advicetypes');

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();

const isAdmin = store.state.user.is_admin;

const newadvice = reactive({
  firstName: '',
  lastName: '',
  phone: '',
  email: '',
  street: '',
  streetNumber: '',
  zip: '',
  city: '',
  advice_status_id: 1,
  type: 0,
  commentary: '',
 // advisor_id: store.state.user.id,
});

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

const r = reactive({
  popupVisible: false,
  selectedBulkOrder: null,
  advisorNames: new Map<number, string>(),
  autoExpand: false,
  newAdvicePopupVisible: false,
});

advisors.load().then(() => {
  advisors.items().forEach((a) => {
    r.advisorNames.set(a.id, a.name);
    console.log(a.name);
  });
});

function openStatus(){
  r.popupVisible = true;
}

function openAdvice(e){
  emit('selectAdviceId', e.row.data.id);
}

onMounted(() => {
  tableHeight.calcHeight();
});

function assignAdvice(id){
  axios.post('api/advices/' + id + '/assign').then(response => response.data).then((advice) => {
    advices.store().push([{ type: "update", data: advice, key: advice.id }]);
  });
}

function isOpenVisible(e){
  const advice = e.row.data as App.Models.Advice;
  return userCanEdit(advice);
}

function rowCanBeEdited(e){
  const advice = e.row.data as App.Models.Advice;
  return userCanEdit(advice);
}

function userCanEdit(advice){
  const userId = store.state.user.id;
  if(isAdmin){
    return true;
  }
  if(advice.advisor_id === userId){
    return true;
  }
  if('shares_ids' in advice && advice.shares_ids.includes(userId)){
    return true;
  }
  return false;
}

function sortedAdvisors(e){
  console.log(e);
  if(!e.data){
    return advisors.store();
  }
  return new CustomStore({
    loadMode: 'raw',
    cacheRawData: true,
    load: (options) => {
      return axios.get('api/advices/' + e.data.id + '/advisors').then(response => response.data);
    }
  });
}

function openNewAdvice(){
  r.newAdvicePopupVisible = true;
}

function saveNewAdvice(){
  axios.post('api/advices', newadvice).then(response => response.data).then((advice) => {
    notify('Beratung erfolgreich angelegt', 'success', 3000);
    advices.store().push([{ type: "insert", data: advice, key: advice.id }]);
    r.newAdvicePopupVisible = false;
    newadvice.firstName = '';
    newadvice.lastName = '';
    newadvice.phone = '';
    newadvice.email = '';
    newadvice.street = '';
    newadvice.streetNumber = '';
    newadvice.zip = '';
    newadvice.city = '';
    newadvice.advice_status_id = 1;
    newadvice.type = 0;
    newadvice.commentary = '';
   // newadvice.advisor_id = store.state.user.id
  }).catch((error) => {
    notify(error.response.data.message, 'error', 3000);
  });
}
</script>
<style scoped>
@media screen and (min-width:680px) {
  .main-table{
    margin: 30px 40px 30px 40px
  }

}
</style>