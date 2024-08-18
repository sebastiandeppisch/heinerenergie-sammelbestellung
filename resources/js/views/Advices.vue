<script setup lang="ts">
import DxTabPanel, {DxItem} from 'devextreme-vue/tab-panel';
import { ref } from 'vue';
import AdvicesTable from './AdvicesTable.vue';
import AdvicesMap from './AdvicesMap.vue';
import Advice from './Advice.vue';
import { store, useStore} from "./../store";
import { useRouter, useRoute } from 'vue-router';
import { Link } from "@inertiajs/vue3";

const defaultIndex = 0;
const adviceIndex = 2;

const selectedIndex = ref(defaultIndex);

const selectedAdviceId = ref(null);

const user = store.getters.user;

const router = useRouter();
const route = useRoute();

if('id' in route.params){
  console.log(route.params.id);
  selectedAdviceId.value = parseInt(route.params.id.toString());
  selectedIndex.value = adviceIndex;
}

console.log(route.path)

if(route.name === 'advicesmap'){
  selectedIndex.value = 1;
}


const onSelectAdvice = (id) => {
  router.push({name: 'advicesid', params: {id: id}})
  selectedAdviceId.value = id;
  if(id == null){
    selectedIndex.value = defaultIndex;
  }else{
    selectedIndex.value = adviceIndex;
  }
}

function onTabChanged(e){
  if(e.addedItems[0].title == "Beratung"){
    router.push({name: 'advicesid', params: {id: selectedAdviceId.value}})
  }else if(e.addedItems[0].title == "Karte"){
    router.push({name: 'advicesmap'})
  }else{
    router.push({name: 'advices'})
  }
}


</script>

<template>
    <div class="dx-card" style="margin: 30px;padding: 10px;" v-if="user.long === null || user.lat === null">
      <i class="dx-icon-info"></i> &nbsp; Trage unter <Link href='profile'>Deinem Profil</Link> Deine Adresse ein, um die Beratungszuteilung zu vereinfachen und um anderen Berater*innen zu zeigen, wo Du beraten möchtest.
    </div>
   <DxTabPanel
      height="100%"
      v-model:selected-index="selectedIndex"
      :loop="false"
      :animation-enabled="false"
      :swipe-enabled="false"
      @selection-changed="onTabChanged"
    >
        <DxItem title="Tabelle" icon="detailslayout">
          <AdvicesTable @selectAdviceId="onSelectAdvice"/>
        </DxItem>
        <DxItem title="Karte" icon="map">
          <AdvicesMap @selectAdviceId="onSelectAdvice"/>
        </DxItem>
        <DxItem title="Beratung" icon="user" :disabled="selectedAdviceId === null">
          <Advice :advice-id="selectedAdviceId"/>
        </DxItem>
    </DxTabPanel>
</template>
