<script setup lang="ts">

import { Checkbox } from '@/shadcn/components/ui/checkbox'
import { v4 as uuidv4 } from 'uuid'


const props = defineProps<{
  items: Array<{ id: string; name: string }>
}>()


const model = defineModel<string[]>({
  default: () => [],
  type: Array,
})

function handleChange(value: boolean | string, itemId: string): any {
  if (value) {
    model.value = [...model.value, itemId]
  } else {
    console.log('Removing item:', itemId);
    model.value = [...model.value.filter(id => id !== itemId)]
  }
}

const uuid = uuidv4();

</script>

<template>
<div class="flex flex-col gap-3">
<div v-for="item in props.items" :key="item.id">
  <div class="flex items-center space-x-2">
		<Checkbox
		:model-value="model.includes(item.id)"
		@update:model-value="(value) => handleChange(value, item.id)"
    :id="`checkbox-${uuid}-${item.id}`"
		/>
    <div class="grid gap-1.5 leading-none">
      <label
        :for="`checkbox-${uuid}-${item.id}`"
        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {{  item.name }}
    </label>

    </div>
  </div>
</div>
</div>
</template>