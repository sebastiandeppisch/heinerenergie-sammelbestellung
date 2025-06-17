<script setup lang="ts">
import { useFilter } from 'reka-ui'
import { computed, ref } from 'vue'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxList } from '@/shadcn/components/ui/combobox'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/shadcn/components/ui/tags-input'

const labelKey = 'name';
const valueKey = 'id';

interface Props {
  items: {
    [labelKey]: string;
    [valueKey]: string;
  }[];
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Suche...',
});

const modelValue = defineModel<string[]>({
  default: () => [],
  type: Array,
});
const open = ref(false)
const searchTerm = ref('')

const { contains } = useFilter({ sensitivity: 'base' })
const filteredItems = computed(() => {
  const options = props.items.filter(i => !modelValue.value.includes(i[labelKey]))
  return searchTerm.value ? options.filter(option => contains(option[labelKey], searchTerm.value)) : options
})

function onFocus() {
    //open.value = true
}

</script>

<template>
  {{ modelValue }}
  <Combobox v-model="modelValue" v-model:open="open" :ignore-filter="true">
    <ComboboxAnchor as-child>
      <TagsInput v-model="modelValue" class="px-2 gap-2 w-full flex flex-col">
      

        <ComboboxInput v-model="searchTerm" as-child @focus="onFocus">
          <TagsInputInput :placeholder="props.placeholder" class="p-0 border-none focus-visible:ring-0 h-auto w-full" @keydown.enter.prevent />
        </ComboboxInput>
        <div class="flex gap-2 flex-wrap items-center">
          <TagsInputItem v-for="item in modelValue" :key="item" :value="item">
            <TagsInputItemText />
            <TagsInputItemDelete />
          </TagsInputItem>
        </div>
      </TagsInput>

      <ComboboxList class="w-[--reka-popper-anchor-width]">
        <ComboboxEmpty />
        <ComboboxGroup>
          <ComboboxItem
            class="w-full"
            v-for="item in filteredItems" :key="item[valueKey]" :value="item[labelKey]"
            @select.prevent="(ev) => {

              if (typeof ev.detail.value === 'string') {
                searchTerm = ''
                modelValue.push(ev.detail.value)
              }

              if (filteredItems.length === 0) {
                open = false
              }
            }"
          >
            {{ item[labelKey] }}
          </ComboboxItem>
        </ComboboxGroup>
      </ComboboxList>
    </ComboboxAnchor>
  </Combobox>
</template>
