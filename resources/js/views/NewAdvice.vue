<template>
  <div style="overflow-x: hidden; white-space: nowrap; width: 480px">
    <Transition :name="r.slide" class="slide-outer">
      <div v-if="r.step === 0">
        <div class="dx-card slide-card">
          <span style="font-size:1.2em">Zuerst brauchen wir Deinen Namen</span>
          <div style="flex-grow: 1"></div>
          <DxTextBox
            v-model="r.firstName"
            placeholder="Erika"
            label="Vorname"
            :is-valid="r.firstName !== ''"
          />
          <div style="flex-grow: 1"></div>
          <DxTextBox
            v-model="r.lastName"
            placeholder="Musterfrau"
            label="Nachname"
            :is-valid="r.lastName !== ''"
          />
          <div style="flex-grow: 1"></div>
          <span style="font-size:1.2em">Das ist nur ein Prototyp, die Daten werden noch nicht gespeichert</span>

          <div style="display: flex">
            <div style="flex-grow: 1"></div>
            <DxButton
              text="Weiter"
              type="default"
              :disabled="r.firstName === '' || r.lastName === ''"
              @click="
                r.slide = 'slide-fw';
                r.step = 1;
              "
            />
          </div>
        </div>
      </div>
    </Transition>
    <Transition :name="r.slide" class="slide-outer">
      <div v-if="r.step === 1">
        <div class="dx-card slide-card" style="height: 400px">
          <span style="font-size:1.2em">Bei was benötigst Du Beratung, {{r.firstName}}?</span>
          <div style="flex-grow: 1"></div>

          <div style="display:flex;flex-direction: row;">
            <div style="width:45px;">
              <font-awesome-icon icon="fa fa-house" style="font-size: 2em;" />
            </div>
           
            <div>
              <DxCheckBox
                v-model="r.advice.place"
                text="Ort (Balkon, Garten, Terrasse, etc.)"
              />
              <div style="margin-left:22px;margin-top:3px;">
                <a href="heinerenergie.de" target="_blank">Mehr Infos zur Aufstellung</a>
              </div>
            </div>
            
          </div>
          <div style="flex-grow: 1"></div>
          <div style="display:flex;flex-direction: row;">
            <div style="width:45px;">
              <font-awesome-icon icon="fa fa-file-signature" style="font-size: 2em;" />
            </div>
            <div>
              <DxCheckBox
                v-model="r.advice.burocrazy"
                text="Bürokratie (Anmeldung, Förderung, etc.)"
              />
              <div style="margin-left:22px;margin-top:3px;">
                <a href="heinerenergie.de" target="_blank">Mehr Infos zum Papierkram</a>
              </div>
            </div>
            
          </div>
          <div style="flex-grow: 1"></div>
          <div style="display:flex;flex-direction: row;">
            <div style="width:45px;">
              <font-awesome-icon icon="fa fa-wrench" style="font-size: 2em;" />
            </div>
            <div>
              <DxCheckBox
                v-model="r.advice.technical"
                text="Technisches (Anschluss, Befestigung, etc.)"
              />
              <div style="margin-left:22px;margin-top:3px;">
                <a href="heinerenergie.de" target="_blank">Mehr Infos zur Technik</a>
              </div>
            </div>
            
          </div>
          <div style="flex-grow: 1"></div>
          <div style="display:flex;flex-direction: row;">
            <div style="width:45px;">
              <!-- TODO fix path -->
            <img src="http://balkon.heinerenergie.de/images/heinerenergie-hochzeitsturm.svg" style="height: 2em;" />
          </div>
            <div>
              <DxCheckBox
                v-model="r.advice.other"
                text="Sonstiges"
              />
              <div style="margin-left:22px;margin-top:3px;">
                <a href="heinerenergie.de" target="_blank">Unsere Webseite</a>
              </div>
            </div>
            
          </div>

          <div style="flex-grow: 1"></div>

          <div style="display: flex">
            <DxButton
              text="Zurück"
              @click="
                r.slide = 'slide-rv';
                r.step = 0;
              "
            />
            <div style="flex-grow: 1"></div>

            <DxButton
              text="Weiter"
              type="default"
              @click="
                r.slide = 'slide-fw';
                r.step = 2;
              "
              :disabled="!r.advice.place && !r.advice.burocrazy && !r.advice.technical && !r.advice.other"
            />
          </div>
        </div>
      </div>
    </Transition>
    <Transition :name="r.slide" class="slide-outer">
      <div v-if="r.step === 2">
        <div class="dx-card slide-card" style="height: 400px">
          <span style="font-size:1.2em">Um Dich zu kontaktieren, brauchen wir Deine Kontaktdaten</span>
          <div style="flex-grow: 1"></div>
         
          <DxTextBox
            v-model="r.email"
            placeholder="max@mustermann.de"
            mode="email"
            label="E-Mail"
            validation-message-mode="always"
            
          >
            <DxValidator  @validated="contactValidated" name="email">
              <DxRequiredRule message="Ohne E-Mail-Adresse können wir Dich nicht kontaktieren"/>
              <DxEmailRule message="Die eingegebene E-Mail Adresse ist ungültig"/>
            </DxValidator>
          </DxTextBox>
          <div style="flex-grow: 1"></div>
          <DxTextBox
            v-model="r.phone"
            placeholder="06151/12345"
            mode="tel"
            label="Telefon"
            validation-message-mode="always"
          >
            <DxValidator @validated="contactValidated" name="phone">
              <DxRequiredRule message="Ohne Telefonnummer können wir Dich nicht kontaktieren"/>
            </DxValidator> 
          </DxTextBox>

          <div style="flex-grow: 1"></div>
          <div style="display: flex">
            <DxButton
              text="Zurück"
              @click="
                r.slide = 'slide-rv';
                r.step = 1;
              "
            />
            <div style="flex-grow: 1"></div>

            <DxButton
              text="Weiter"
              type="default"
              @click="
                r.slide = 'slide-fw';
                r.step = 3;
              "
              :disabled="r.mailValid === false || r.phoneValid === false || r.email === '' || r.phone === ''"
            />
          </div>
        </div>
      </div>
    </Transition>
    <Transition :name="r.slide" class="slide-outer">
      <div v-if="r.step === 3">
        <div class="dx-card slide-card">
          <span style="font-size:1.2em">Wie möchtest Du beraten werden?</span>
          <div style="flex-grow: 1"></div>
          <div style="display: flex;">
            <div style="flex-grow: 1"></div>
            <div>
              <DxButton
                type="default"
                icon="fa fa-phone"
                text="Telefon/Video"
                @click="
                  r.slide = 'slide-fw';
                  r.step = 5;"
              >
                <template #default>
                  <font-awesome-icon icon="fa fa-phone" style="margin-right:8px"/>
                   
                  <span class="dx-button-text">Telefon/Video</span>
                </template>
              </DxButton>
            </div>
            <div style="flex-grow: 1"></div>
            <div>
              <DxButton
                icon="home"
                text="Vor Ort"
                type="default"
                @click="
                  r.slide = 'slide-fw';
                  r.step = 4;"
              />
            </div>
            <div style="flex-grow: 1"></div>
          </div>

          <div style="flex-grow: 1"></div>
          <div style="display: flex">
            <DxButton
              text="Zurück"
              @click="
                r.slide = 'slide-rv';
                r.step = 2;
              "
            />
            <div style="flex-grow: 1"></div>

            <DxButton
              text="Weiter"
              type="default"
              @click="
                r.slide = 'slide-fw';
                r.step = 4;
              "
              :disabled="r.contact === null"
            />
          </div>
        </div>
      </div>
    </Transition>
    <Transition :name="r.slide" class="slide-outer">
      <div v-if="r.step === 4">
        <div class="dx-card slide-card">
          <span style="font-size:1.2em">Wo möchtest Du beraten werden?</span>
          <div style="flex-grow: 1"></div>
          <div>
            <div style="display:flex;">
              <DxTextBox
                placeholder="Luisenstraße"
                width="100%"
                :element-attr="{style: 'margin-right: 16px'}"
                label="Straße"
                v-model="r.street"
              />
              <DxTextBox
                placeholder="42a"
                width="100px"
                label="Nr."
                v-model="r.streetNumber"
              />
            </div>
            <div  style="display:flex;margin-top:16px;">
              <DxTextBox
                placeholder="64283"
                width="100px"
                label="Plz"
                v-model="r.zip"
              />
              <DxTextBox
                placeholder="Darmstadt"
                :element-attr="{style: 'margin-left: 16px'}"
                label="Ort"
                width="100%"
                v-model="r.city"
              />
            </div>
          </div>
          <div style="flex-grow: 1"></div>
          <DxButton
              text="Beratungsanfrage abschicken"
              type="default"
              @click="
                r.slide = 'slide-fw';
                r.step = 4;
              "
              :disabled="r.street === '' || r.streetNumber === '' || r.zip === '' || r.city === ''"
            />

          <div style="flex-grow: 1"></div>
          <div style="display: flex">
            <DxButton
              text="Zurück"
              @click="
                r.slide = 'slide-rv';
                r.step = 2;
              "
            />
            <div style="flex-grow: 1"></div>

            <DxButton
              text="Weiter"
              type="default"
              @click="
                r.slide = 'slide-fw';
                r.step = 5;
              "
              :disabled="r.contact === null"
            />
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import {
  DxValidator,
  DxRequiredRule,
  DxCompareRule,
  DxEmailRule,
  DxPatternRule,
  DxStringLengthRule,
  DxRangeRule,
  DxAsyncRule,
} from 'devextreme-vue/validator';
import DxTextBox from "devextreme-vue/text-box";
import LaravelLookupSource from "../LaravelLookupSource";
import DxButton from "devextreme-vue/button";

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxSummary,
  DxTotalItem,
  DxLookup,
} from "devextreme-vue/data-grid";

import axios from "axios";
import { ref, reactive, computed, onMounted } from "vue";

import DataSource from "devextreme/data/data_source";
import CustomStore from "devextreme/data/custom_store";
import { CustomSummaryInfo } from "devextreme/ui/data_grid";
import notify from "devextreme/ui/notify";
import ProductDetail from "../components/ProductDetail.vue";

import OrderForm from "../components/OrderForm.vue";
import OrderSaved from "../components/OrderSaved.vue";

import { formatPrice, formatPriceCell, notifyError } from "./../helpers";
import { ValidationResult } from "devextreme/ui/validation_group";

import { useRoute } from "vue-router";
import { useStore } from "../store";
import auth from "../auth";

import DxCheckBox from "devextreme-vue/check-box";
import DxRadioGroup from "devextreme-vue/radio-group";
const r = reactive({
  step: 0,
  slide: "slide-fw",
  firstName: "",
  lastName: "",
  advice: {
    place: false,
    technical: false,
    burocrazy: false,
    other: false
  },
  email: "",
  phone: "",
  contact: null,
  zip: '',
  city: '',
  street: '',
  streetNumber: '',
  saving: false,
  mailValid: false,
  phoneValid: false,
});

function contactValidated(e){
  if(e.name === 'email'){
    r.mailValid = e.isValid;
  }

  if(e.name === 'phone'){
    r.phoneValid = e.isValid;
  }
}
</script>
<style scoped>
.slide-fw-enter-active,
.slide-fw-leave-active,
.slide-rv-enter-active,
.slide-rv-leave-active {
  transition: left 0.8s;
}

.slide-fw-enter-to {
  position: relative;
  left: -440px;
}

.slide-fw-enter-from {
  position: relative;
  left: 0px;
}

.slide-fw-leave-to {
  position: relative;
  left: -440px;
}

.slide-fw-leave-from {
  position: relative;
  left: 0;
}

.slide-rv-enter-to {
  position: relative;
  left: 0px;
}

.slide-rv-enter-from {
  position: relative;
  left: -440px;
}

.slide-rv-leave-to {
  position: relative;
  left: 0px;
}

.slide-rv-leave-from {
  position: relative;
  left: -440px;
}

.slide-card {
  overflow: auto;
  white-space: normal;
  width: 400px;
  height: 400px;
  margin: 20px;
  padding: 20px;
  display: flex;
  flex-direction: column;
}

.slide-outer {
  display: inline-block;
}
</style>