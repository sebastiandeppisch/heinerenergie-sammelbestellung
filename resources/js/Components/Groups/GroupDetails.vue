<template>
  <div class="group-details pt-6">
    <div class="flex justify-end mb-4">
      <DxButton
        text="Löschen"
        type="danger"
        stylingMode="text"
        icon="trash"
        @click="confirmDelete"
      />
    </div>
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Group name -->
      <div>
        <DxTextBox
          v-model="form.name"
          label="Name"
          labelMode="floating"
          :is-valid="form.errors.name === undefined"
        />
        <div v-if="form.errors.name" class="text-red-500 text-sm">  {{ form.errors.name }}</div>
      </div>

      <!-- Group description -->
      <div>
        <DxTextArea
          v-model="form.description"
          label="Beschreibung"
          labelMode="floating"
          :height="100"
        />
        <div v-if="form.errors.description" class="text-red-500 text-sm">  {{ form.errors.description }}</div>
      </div>

      <!-- Group logo -->
      <div>
        <label class="text-sm font-medium text-gray-700 mb-2 block">Logo</label>
        <div class="flex items-center">
          <img
            :src="logoSrc"
            :alt="group.name"
            class="w-12 h-12 rounded mr-4"
          />
          <input
            type="file"
            ref="logoInput"
            class="hidden"
            accept="image/*"
            @change="handleLogoChange"
          />
          <DxButton
            text="Logo auswählen"
            stylingMode="outlined"
            @click="logoInput?.click()"
          />
          <DxButton
            v-if="logoSrc && !form.remove_logo"
            text="Logo Entfernen"
            stylingMode="outlined"
            type="danger"
            class="ml-2"
            @click="removeLogo"
          />
        </div>
        <div v-if="form.errors.logo" class="text-red-500 text-sm">  {{ form.errors.logo }}</div>
      </div>

      <div>
        <DxCheckBox
          v-model="form.accepts_transfers"
          text="Beratungen von anderen Initiativen akzeptieren"
        />
        <div v-if="form.errors.accepts_transfers" class="text-red-500 text-sm">  {{ form.errors.accepts_transfers }}</div>
      </div>

      <div class="flex justify-end">
        <DxButton
          text="Speichern"
          type="default"
          stylingMode="contained"
          @click="handleSubmit"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  DxTextBox,
  DxTextArea,
  DxButton,
  DxCheckBox
} from 'devextreme-vue'
import { confirm } from 'devextreme/ui/dialog'
import { route } from 'ziggy-js'

type GroupData = App.Data.GroupData;

const props = defineProps<{
  group: GroupData
}>()

const logoSrc = computed(() => {
  if (form.logo) {
    return URL.createObjectURL(form.logo)
  }

  if (props.group.logo_path) {
    return props.group.logo_path
  }

  return '/img/example_img.svg'
})


type FormData = Omit<GroupData, 'id' | 'logo_path' | 'parent_id' | 'users_count' | 'advices_count' | 'userCanActAsAdmin'> & {
  logo: File | null
  remove_logo: boolean,
  _method: string
}

const form = useForm<FormData>({
  name: props.group.name,
  description: props.group.description,
  accepts_transfers: props.group.accepts_transfers,
  logo: null,
  remove_logo: false,
  _method: 'PUT'
})

const logoInput = ref<HTMLInputElement | null>(null)

const handleLogoChange = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    form.logo = file
    form.remove_logo = false
  }
}

const removeLogo = () => {
  form.logo = null
  if (logoInput.value) {
    logoInput.value.value = ''
  }
  form.remove_logo = true
}

const handleSubmit = () => {
  form.post(route('groups.update', props.group.id), {
    preserveScroll: true,
    forceFormData: true
  })
}

const confirmDelete = () => {
  confirm('Soll diese Initiative wirklich gelöscht werden?', 'Initiative löschen')
    .then((result) => {
      if (result) {
        form.delete(route('groups.destroy', props.group.id), {
          preserveScroll: true
        })
      }
    })
}
</script>