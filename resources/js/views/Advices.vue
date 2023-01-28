<template>
   <DxTabPanel
      height="100%"
      v-model:selected-index="selectedIndex"
      :loop="false"
      :animation-enabled="false"
      :swipe-enabled="false"
    >
        <DxItem title="Tabellenansicht" icon="detailslayout">
          <AdvicesTable @selectAdviceId="onSelectAdvice"/>
        </DxItem>
        <DxItem title="Kartenansicht" icon="map">
          <AdvicesMap @selectAdviceId="onSelectAdvice"/>
        </DxItem>
        <DxItem title="Beratung" icon="user" :disabled="selectedAdviceId === null">
          <Advice :advice-id="selectedAdviceId"/>
        </DxItem>
    </DxTabPanel>
</template>

<script setup lang="ts">
import DxTabPanel, {DxItem} from 'devextreme-vue/tab-panel';
import { ref } from 'vue';
import AdvicesTable from './AdvicesTable.vue';
import AdvicesMap from './AdvicesMap.vue';
import Advice from './Advice.vue';

const defaultIndex = 0;
const adviceIndex = 2;

const selectedIndex = ref(defaultIndex);

const selectedAdviceId = ref(null);

const onSelectAdvice = (id) => {
  console.log('selectAdvice in Advices', id);
  selectedAdviceId.value = id;
  console.log(selectedAdviceId.value)
  if(id == null){
    selectedIndex.value = defaultIndex;
  }else{
    selectedIndex.value = adviceIndex;
  }
}

</script>
