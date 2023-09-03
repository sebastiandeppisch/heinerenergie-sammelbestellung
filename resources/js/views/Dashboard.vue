<template>
  <div ref="outer">
    <h2 class="content-block">Dashboard</h2>
    <p class="dx-card content" style="padding:16px;margin:32px;" >
      <h2>Berater*innen-Infos</h2>
      <SettingHtml setting="advisorInfo" />
    </p>
  </div>
</template>

<script setup lang="ts">
import SettingHtml from '../components/SettingHtml.vue';


let orderFormText = () => 'advisorInfo';
</script>
<style scoped>
.flex-row{
  flex-direction: row;
  display: flex;
  width: 100%;
}
.flex-cell{
  /*padding: 30px;*/
  flex: 1;
}

.label{
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 16px;
}
</style>