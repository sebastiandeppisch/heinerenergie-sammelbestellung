<template>


  <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-1">
        <label class="block text-sm font-medium mb-2">Formulare</label>
          <div class="flex flex-wrap gap-1 items-center w-full">

            <CheckBoxGroup
              :items="props.formDefinitions"
              v-model="selectedFormTypes"
            />

          </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Zeitraum</label>
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            TODO Datepicker
          </div>

        </div>
      </div>

      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-2">Sortierung</label>
          <Button
            @click="toggleSortOrder"
            :variant="sortOrder === 'desc'?'outline':'secondary'"
            class="justify-between flex items-center"
          >
            <span>Datum {{ sortOrder === 'desc' ? 'Neueste zuerst' : 'Älteste zuerst' }}</span>
            <ArrowDown v-if="sortOrder === 'desc'" class="h-4 w-4" />
            <ArrowUp v-else class="h-4 w-4" />
          </Button>

        <div>
          <label class="block text-sm font-medium mb-2">Darstellung</label>
          <Button
            @click="toggleGrouping"
            :variant="groupByForm ? 'secondary' : 'outline'"
            class="w-full justify-between flex items-center"
          >
            <span>Nach Formularen gruppieren</span>
            <Group v-if="groupByForm" class="h-4 w-4" />
            <List v-else class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import { X, ArrowDown, ArrowUp, Ungroup, Group, List } from 'lucide-vue-next'
import TagComboBox from '@/shadcn/components/TagComboBox.vue';
import Button from '@/shadcn/components/ui/button/Button.vue'
import CheckBoxGroup from '@/shadcn/components/CheckboxGroup.vue'
const selectedFormTypes = defineModel<string[]>('selectedFormTypes', {
  default: () => []
})

const dateRange = defineModel<{ from: Date | null; to: Date | null }>('dateRange', {
  default: () => ({ from: null, to: null })
})

const sortOrder = defineModel<'asc' | 'desc'>('sortOrder', {
  default: 'asc'
})

const groupByForm = defineModel<boolean>('groupByForm', {
  default: false
})

const props = defineProps<{
  formDefinitions: App.Data.FormDefinitionData[]
}>();

const hasActiveFilters = computed<boolean>(() => {

  console.log(selectedFormTypes.value, selectedFormTypes.value.length);

  return selectedFormTypes.value.length > 0 /* ||
         dateRange.value.from !== null ||
         dateRange.value.to !== null*/
})

const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc'
}

const toggleGrouping = () => {
  groupByForm.value = !groupByForm.value
}

const clearAllFilters = () => {
  selectedFormTypes.value = []
  dateRange.value = { from: null, to: null }
}
</script>
