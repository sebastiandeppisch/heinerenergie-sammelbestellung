<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PolygonMap from '@/components/PolygonMap.vue'
import { ref, watch } from 'vue'
import { route } from 'ziggy-js'
import DxButton from "devextreme-vue/button";


const props = defineProps<{
  group: App.Data.GroupData,
  polygon: App.ValueObjects.Polygon
}>()

const form = useForm({
  polygon: props.polygon || []
})

const hasChanges = ref(false)

watch(() => form.polygon, (newPolygon) => {
  hasChanges.value = JSON.stringify(newPolygon) !== JSON.stringify(props.polygon || [])
}, { deep: true })

watch(() => props.polygon, (newPolygon) => {
  form.polygon = newPolygon || []
}, { deep: true })

const handleSave = () => {
  form.post(route('groups.consulting-area.update', props.group.id), {
    preserveScroll: true,
    onSuccess: () => {
      hasChanges.value = false
    }
  })
}

const handleDelete = () => {
  form.delete(route('groups.consulting-area.delete', props.group.id), {
    preserveScroll: true
  })
}

</script>

<template>
  <div class="space-y-4">
    <div class="mt-4">
      <p class="text-sm text-gray-600 text-right">
        Klicke auf das Polygon-Symbol oben rechts in der Karte, um den Beratungsbereich zu bearbeiten.
      </p>
    </div>

    <div class="w-full h-[600px]">
      <PolygonMap
        v-model="form.polygon"
        class="rounded-lg"
        logo="http://[::1]:5173/resources/img/logo.png"
        :logo-aspect="2.84"
      />
    </div>

    <div class="mt-4 flex justify-between">
      <div>
        <DxButton
          v-if="props.polygon"
          text="Beratungsgebiet löschen"
          :disabled="form.processing || form.polygon.coordinates.length === 0"
          stylingMode="outlined"
          icon="trash"
          type="danger"
          @click="handleDelete"
        />
      </div>
      <div>
        <DxButton
          text="Beratungsgebiet speichern"
          :disabled="form.processing || !hasChanges"
          stylingMode="contained"
          @click="handleSave"
          type="default"
        />
      </div>
    </div>
  </div>
</template>
