<template>
    <div class="donut-chart-container">
        <div v-if="loading" class="loading-indicator">Laden...</div>
        <div v-else>
            <VueApexCharts type="donut" height="350" :options="chartOptions" :series="series" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
// Typdefinitionen
interface CurrentStatusData {
    statusCounts: Record<string, number>;
}

// Props definieren
const props = defineProps<{
    data: CurrentStatusData;
}>();

// Reaktive Zustände
const loading = ref(false);

// Berechnete Eigenschaften
const series = computed(() => Object.values(props.data.statusCounts));
const labels = computed(() => Object.keys(props.data.statusCounts));

const chartOptions = computed(() => {
    return {
        chart: {
            type: 'donut',
            fontFamily: 'Arial, sans-serif',
            background: 'transparent',
        },
        colors: ['#FBBC05', '#4285F4', '#34A853', '#EA4335'],
        labels: labels.value,
        legend: {
            position: 'bottom',
            fontSize: '13px',
            fontFamily: 'Arial, sans-serif',
            markers: {
                width: 12,
                height: 12,
                radius: 12,
            },
            formatter: function (seriesName: string, opts: any) {
                return [seriesName, ' - ', opts.w.globals.series[opts.seriesIndex]];
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val: number, opts: any) {
                return opts.w.globals.series[opts.seriesIndex] + ' (' + Math.round(val) + '%)';
            },
            style: {
                fontSize: '12px',
                fontFamily: 'Arial, sans-serif',
            },
            dropShadow: {
                enabled: false,
            },
        },
        plotOptions: {
            pie: {
                customScale: 0.9,
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 600,
                            offsetY: -10,
                        },
                        value: {
                            show: true,
                            fontSize: '16px',
                            fontWeight: 400,
                            formatter: function (val: string) {
                                return val;
                            },
                        },
                        total: {
                            show: true,
                            label: 'Gesamt',
                            fontSize: '16px',
                            fontWeight: 600,
                            formatter: function (w: any) {
                                return w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0);
                            },
                        },
                    },
                },
            },
        },
        stroke: {
            width: 0,
        },
        tooltip: {
            y: {
                formatter: function (val: number) {
                    return val.toString();
                },
            },
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        height: 300,
                    },
                    legend: {
                        position: 'bottom',
                        offsetY: 0,
                    },
                },
            },
        ],
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.8,
                },
            },
        },
    };
});

// Methoden
const getTotal = (): number => {
    return Object.values(props.data.statusCounts).reduce((a, b) => a + b, 0);
};
</script>

<style scoped>
.donut-chart-container {
    position: relative;
    min-height: 350px;
}

.loading-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 16px;
    color: #666;
}
</style>
