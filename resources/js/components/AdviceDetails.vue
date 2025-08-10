<script setup lang="ts">
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import otherHelpTypeImage from '@/../img/heinerenergie-hochzeitsturm.svg';

const props = defineProps<{
  advice: App.Data.DataProtectedAdviceData
}>();
</script>

<template>
  <div class="advice-details">
    <div class="detail-section">
      <h3 class="section-title">Benötigt Hilfe bei:</h3>
      <div class="help-types">
        <div v-if="advice.helpType_place" class="help-type">
          <font-awesome-icon icon="fa fa-house" class="icon" />
          <span>Ort (Balkon, Garten, Terrasse, etc.)</span>
        </div>
        <div v-if="advice.helpType_bureaucracy" class="help-type">
          <font-awesome-icon icon="fa fa-file-signature" class="icon" />
          <span>Bürokratie (Anmeldung, Förderung, etc.)</span>
        </div>
        <div v-if="advice.helpType_technical" class="help-type">
          <font-awesome-icon icon="fa fa-wrench" class="icon" />
          <span>Technisches (Anschluss, Befestigung, etc.)</span>
        </div>
        <div v-if="advice.helpType_other" class="help-type">
          <img
            :src="otherHelpTypeImage"
            class="custom-icon"
          />
          <span>Andere Themen</span>
        </div>
        <div v-if="!advice.helpType_place && !advice.helpType_bureaucracy && !advice.helpType_technical && !advice.helpType_other" class="no-data">
          <span>Keine Angabe</span>
        </div>
      </div>
    </div>

    <div class="detail-section">
      <h3 class="section-title">Gebäudeart:</h3>
      <div class="building-info">
        <div v-if="advice.houseType === 0" class="info-item">
          <font-awesome-icon icon="fa fa-home" class="icon" />
          <span>Einfamilienhaus</span>
        </div>
        <div v-if="advice.houseType === 1" class="info-item">
          <font-awesome-icon icon="fa fa-building" class="icon" />
          <span>Mehrfamilienhaus</span>
        </div>
        <div v-else class="no-data">
          <span>Keine Angabe/Sonstiges</span>
        </div>
      </div>

      <h3 class="section-title">Vermieter*in / WEG vorhanden?</h3>
      <div class="landlord-info">
        <div :class="['status-badge', advice.landlordExists ? 'yes' : 'no']">
          {{ advice.landlordExists ? 'Ja' : 'Nein' }}
        </div>
      </div>
    </div>

    <div class="detail-section">
      <h3 class="section-title">Wo soll das Steckersolargerät installiert werden?</h3>
      <div class="installation-info">
        <p v-if="advice.placeNotes" class="notes">{{ advice.placeNotes }}</p>
        <p v-else class="no-data">Keine Angabe</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.advice-details {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.detail-section {
  margin-bottom: 24px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 12px;
}

.help-types, .building-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.help-type, .info-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 14px;
}

.icon {
  color: #3498db;
  width: 16px;
}

.custom-icon {
  height: 16px;
  width: 16px;
}

.no-data {
  color: #6c757d;
  font-style: italic;
  font-size: 14px;
  padding: 8px 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 500;
}

.status-badge.yes {
  background-color: #e1f0ff;
  color: #3498db;
}

.status-badge.no {
  background-color: #f8f9fa;
  color: #6c757d;
}

.installation-info {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 12px;
}

.notes {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: #2c3e50;
}
</style> 