<template>
  <div v-if="adviceId !==null">
    <div v-if="advice !== null" style="padding:20px;">
      <h2>Beratung</h2>
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
        <DxRadioGroup
          :items="[{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}]"
          displayExpr="name"
          valueExpr="id"
          v-if="false"
        />
      </div>
    </div>
    <div v-else style="height:100%;width:100%;min-height:300px" id="advice-loading" >
      {{ adviceId }}
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
    
  </div>
</template>

<script setup lang="ts">
import DxTextArea from 'devextreme-vue/text-area';
import DxRadioGroup from 'devextreme-vue/radio-group';
import { DxForm, DxItem, DxSimpleItem, DxGroupItem, DxButtonItem} from 'devextreme-vue/form';
import { DxLoadPanel } from 'devextreme-vue/load-panel';
import { ref, onMounted, reactive, defineProps, PropType, watch } from "vue";
import LaravelDataSource from "../LaravelDataSource";
import notify from 'devextreme/ui/notify';

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
        }
      });
    });
}

function onSubmit(){
  advicesDataSource.store().update(props.adviceId, advice.value).then((result) => {
    notify("Beratung gespeichert", "success", 2000);
  });
}

if(props.adviceId !== null){
  fetchAdvice(props.adviceId);
}
</script>