<script setup lang="ts">
import { reactive } from '@vue/reactivity';
import axios from 'axios';

interface Props{
  setting: string
}
const props = defineProps<Props>();

const state = reactive({ setting: 'Wird geladen...'});

axios.get('/api/html/' + props.setting).then(response => {
  state.setting = response.data.html;
});

</script>

<template>
  <div v-html="state.setting">
  </div>
</template>
