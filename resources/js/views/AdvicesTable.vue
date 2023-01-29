<template>
  <div ref="outer">
    <h2 class="content-block">Beratungen</h2>
    <div style="margin: 30px 40px 30px 40px">
      <DxDataGrid
        class="dx-card wide-card"
        :data-source="advices"
        :show-borders="false"
        :column-auto-width="true"
        :column-hiding-enabled="true"
        :height="reactiveHeight.height"
        min-height="450px"
      >
        <DxScrolling mode="virtual" />
        <DxFilterRow :visible="true" />
        <DxEditing
          :allow-updating="true"
          :allow-adding="true"
          :allow-deleting="true"
          mode="cell"
        />
        <DxToolbar>
          <DxItem
            location="before"
            template="test"
          />
          <DxItem
            location="after"
            name="addRowButton"
          />
        </DxToolbar>
        <template #test>
          <DxButton
            icon="fields"
            text="Status editieren"
            @click="openStatus"
          />        
        </template>
        <DxColumn type="buttons" caption="Öffnen">
          <DxTableButton
            hint="Beratung öffnen"
            icon="user"
            text="Öffnen"
            @click="openAdvice"
          />
        </DxColumn>
        <DxColumn data-field="advice_status_id" caption="Status">
          <DxLookup
            :data-source="adviceStatus"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="advisor_id" caption="Berater*in">
          <DxLookup
            :data-source="advisors"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>
        <DxColumn data-field="distance" caption="Luftlinie" cell-template="distance" :allow-editing="false"/>
        <DxColumn data-field="type" caption="Beratungstyp">
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
        <DxColumn data-field="street"       caption="Straße"          :allow-editing="false"/>
        <DxColumn data-field="zip"          caption="Plz"             :allow-editing="false"/>
        <DxColumn data-field="city"         caption="Stadt"           :allow-editing="false"/>

        <DxSummary :recalculate-while-editing="true">
          <DxTotalItem column="advice_status_id" summary-type="count" />
        </DxSummary>
        <template #distance="{data}">
          <PhysicalValue :value="data.data.distance" unit="m" />   
        </template>
        <template #street="{data}">
          <div>{{ data.data.street }}  {{ data.data.streetNumber }}</div>
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
  DxButton as DxTableButton
} from "devextreme-vue/data-grid";
import LaravelLookupSource from "../LaravelLookupSource";
import DxButton from "devextreme-vue/button";

const emit = defineEmits(["selectAdviceId"])

const advices = new LaravelDataSource("api/advices");
const advisors = new LaravelLookupSource("api/users");
const adviceStatus = new LaravelLookupSource('api/advicestatus');
const adviceTypes = new LaravelLookupSource('api/advicetypes');

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();

const r = reactive({
  popupVisible: false,
  selectedBulkOrder: null
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
</script>
