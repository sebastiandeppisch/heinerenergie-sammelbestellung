<template>
    <div class="grid grid-cols-2 gap-6 max-sm:grid-cols-1">
        <AdviceStatusDistributionChart />
        <AdviceMonthlyCountChart />
        <CurrentStatusDistributionChart />
    </div>
</template>

<script setup lang="ts">
import AdviceStatusDistributionChart from './charts/AdviceStatusDistributionChart.vue';
import AdviceMonthlyCountChart from './charts/AdviceMonthlyCountChart.vue';
import CurrentStatusDistributionChart from './charts/CurrentStatusDistributionChart.vue';
</script>
