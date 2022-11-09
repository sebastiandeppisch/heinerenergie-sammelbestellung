<template>
  	<span>{{formattedValue}}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
interface Props {
  value: number|null,
  unit: string,
}
const p = defineProps<Props>();


const formattedValue = computed(() => {
  if(p.value === null){
    return '';
  }
  let n = p.value ?? 0;

  //n = n * Math.pow(10, this.exp);

  var ranges = [
    { divider: 1e18 , suffix: 'P' },
    { divider: 1e15 , suffix: 'E' },
    { divider: 1e12 , suffix: 'T' },
    { divider: 1e9 , suffix: 'G' },
    { divider: 1e6 , suffix: 'M' },
    { divider: 1e3 , suffix: 'k' }
  ];
  for (var i = 0; i < ranges.length; i++) {
    if (n >= ranges[i].divider) {
      return (n / ranges[i].divider).toPrecision(3).toString() +' '+ ranges[i].suffix + p.unit;
    }
  }
  return n.toPrecision(3).toString() + " " + p.unit;
})
</script>
