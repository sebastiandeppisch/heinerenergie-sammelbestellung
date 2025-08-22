<template>
    <div class="area-chart-container">
        <div v-if="loading" class="loading-indicator">Laden...</div>
        <div v-else>
            <VueApexCharts type="area" height="350" :options="chartOptions" :series="series" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// Typdefinitionen
interface StatusData {
    date: string;
    statusCounts: Record<string, number>;
}

// Props definieren
const props = defineProps<{
    data: StatusData[];
}>();

// Reaktive Zustände
const loading = ref(false);

// Berechnete Eigenschaften
const series = computed(() => {
    // Alle Status-Typen aus allen Datenpunkten extrahieren
    const statusTypes = [...new Set(props.data.flatMap((item) => Object.keys(item.statusCounts)))];

    // Für jeden Status-Typ eine Datenreihe erstellen
    return statusTypes.map((status) => ({
        name: status,
        data: props.data.map((item) => item.statusCounts[status] || 0),
    }));
});

const chartOptions = computed(() => {
    return {
        chart: {
            type: 'area',
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Arial, sans-serif',
            background: 'transparent',
        },
        colors: ['#FBBC05', '#4285F4', '#34A853', '#EA4335'],
        dataLabels: {
            enabled: false,
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.7,
                opacityTo: 0.9,
                shadeIntensity: 1,
                stops: [0, 90, 100],
            },
        },
        grid: {
            borderColor: '#e0e0e0',
            padding: {
                right: 10,
                left: 10,
            },
        },
        xaxis: {
            categories: props.data.map((item) => {
                // Formatierung des Datums (YYYY-MM-DD zu MMM YYYY)
                const date = new Date(item.date);
                return date.toLocaleDateString('de-DE', { month: 'short', year: 'numeric' });
            }),
            labels: {
                style: {
                    colors: '#616161',
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            title: {
                text: 'Anzahl Beratungen',
                style: {
                    fontSize: '14px',
                    fontWeight: 'normal',
                    color: '#616161',
                },
            },
            labels: {
                style: {
                    colors: '#616161',
                    fontSize: '12px',
                },
                formatter: (value: number) => Math.round(value),
            },
        },
        tooltip: {
            shared: true,
            followCursor: true,
            x: {
                format: 'MMM yyyy',
            },
            y: {
                formatter: function (val: number) {
                    return Math.round(val);
                },
            },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '13px',
            markers: {
                radius: 3,
            },
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        height: 250,
                    },
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        ],
    };
});
</script>

<style scoped>
.area-chart-container {
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
