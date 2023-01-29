<template>
    <div class="dx-card" style="margin: 30px;padding: 10px;" v-if="user.long === null || user.lat === null">
      <i class="dx-icon-info"></i> &nbsp; Trage unter <router-link to='profile'>Deinem Profil</router-link> Deine Adresse ein, um die Beratungszuteilung zu vereinfachen und um anderen Berater*innen zu zeigen, wo Du beraten möchtest.
    </div>
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
import { store, useStore} from "./../store";

const defaultIndex = 0;
const adviceIndex = 2;

const selectedIndex = ref(defaultIndex);

const selectedAdviceId = ref(null);

const user = store.getters.user;


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
