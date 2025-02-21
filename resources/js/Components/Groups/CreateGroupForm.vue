<template>
  <div class="group-form">
    <form @submit.prevent="submit">
      <!-- Group name -->
      <div class="mb-4">
        <DxTextBox
          v-model="form.name"
          label="Name"
          labelMode="floating"
          :show-clear-button="true"
          :required="true"
        />
        <div v-if="form.errors.name" class="text-red-500 text-sm">  {{ form.errors.name }}</div>
      </div>

      <!-- Parent Group -->
      <div class="mb-4">
        <DxSelectBox
          v-model="form.parent_id"
          :data-source="parentGroups"
          display-expr="name"
          value-expr="id"
          label="Übergeordnete Initiative"
          labelMode="floating"
          :show-clear-button="!parentRequired"
          :required="parentRequired"
        />
        <div v-if="form.errors.parent_id" class="text-red-500 text-sm">  {{ form.errors.parent_id }}</div>
      </div>

      <!-- Action buttons -->
      <div class="flex justify-end space-x-3">
        <DxButton
          text="Erstellen"
          type="default"
          styling-mode="contained"
          :disabled="form.processing"
          submit-button="true"
          @click="submit"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { DxTextBox, DxSelectBox, DxButton } from 'devextreme-vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import notify from 'devextreme/ui/notify';

type GroupData = App.Data.GroupData;

const emit = defineEmits<{
  (e: 'close'): void
}>();

const props = defineProps<{
  parentGroups: GroupData[]
  parentRequired: boolean
}>();

const form = useForm<{
  name: string,
  parent_id: number | null
}>({
  name: '',
  parent_id: null
});

const submit = () => {

  if(form.name.length === 0) {
    notify('Bitte gib einen Namen für die Initiative ein.', 'error');
    return;
  }

  if(form.parent_id === null && props.parentRequired) {
    notify('Du kannst nur Untergruppen erstellen, wenn du eine übergeordnete Initiative auswählst.', 'error');
    return;
  }

  form.post(route('groups.store'), {
    onSuccess: () => {
      emit('close')
    }
  })
}
</script>