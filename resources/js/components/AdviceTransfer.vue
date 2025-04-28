<script setup lang="ts">
import { ref } from 'vue';
import { DxSelectBox, DxTextArea, DxButton, DxPopup } from 'devextreme-vue';
import notify from 'devextreme/ui/notify';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faWarning } from '@fortawesome/free-solid-svg-icons';

const props = defineProps<{
  adviceId: string
  transferableGroups: Array<App.Data.GroupData>
}>();

const popupVisible = ref(false);
const reason = ref('');
const selectedGroup = ref<string | null>(null);

function showTransferPopup() {
  popupVisible.value = true;
}

function transferAdvice() {
  if (!selectedGroup.value) {
    notify('Bitte wähle eine Initiative aus', 'error', 2000);
    return;
  }

  if(!reason.value) {
    notify('Bitte gib einen Grund für die Übertragung ein', 'error', 2000);
    return;
  }

  router.post(`/advices/${props.adviceId}/transfer`, {
    group_id: selectedGroup.value,
    reason: reason.value
  }, {
    onSuccess: () => {
      popupVisible.value = false;
    }
  });
}
</script>

<template>
	<div>
    <DxButton
      text="Beratung übertragen"
      @click="showTransferPopup"
    />

    <DxPopup
      v-model:visible="popupVisible"
      :drag-enabled="false"
      :show-close-button="true"
      width="auto"
      height="auto"
      title="Beratung übertragen"
    >
      <div class="popup-content">
        <DxSelectBox
          :data-source="transferableGroups"
          display-expr="name"
          value-expr="id"
          v-model="selectedGroup"
          label="Initiative auswählen"
          label-mode="floating"
          :show-clear-button="true"
        />

        <div class="mt-4">
          <DxTextArea
            v-model="reason"
            label="Grund für die Übertragung"
            label-mode="floating"
            :height="100"
          />
        </div>

		<div class="transfer-info mt-4">
			<p class="info-text">
				Die Beratung wird an die ausgewählte Initiative übertragen. Der/Die Klient:in wird per E-Mail benachrichtigt.
			</p>
			<p class="warning-text">
				<FontAwesomeIcon :icon="faWarning" /> Du hast danach evtl. keine Berechtigung mehr, die Beratung zu sehen.
			</p>
		</div>

        <div class="mt-4">
          <DxButton
            text="Übertragen"
            type="default"
            @click="transferAdvice"
            width="100%"
          />
        </div>
      </div>
    </DxPopup>
	</div>
</template>

<style scoped>
.transfer-container {
  padding: 24px;
}

.mt-4 {
  margin-top: 16px;
}

.popup-content {
  padding: 20px;
}

.transfer-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
}

.warning-text {
  color: red;
}


.info-text {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
}
</style>
