<script setup lang="ts">
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

const props = defineProps<{
  advice: App.Models.Advice
}>();
</script>

<template>
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
      <b>Vermieter*in / WEG vorhanden?</b> 
      <div v-if="advice.landlordExists">Ja</div>
      <div v-else>Nein</div> 
    </div>
    <div>
      <b>Wo soll das Steckersolargerät installiert werden?</b>
      <div v-if="advice.placeNotes">
        {{ advice.placeNotes }}
      </div>
      <div v-else><i>Keine Angabe</i></div>
    </div>
  </div>
</template> 