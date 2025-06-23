<template>
  <div class="line-chart-container">
    <div v-if="loading" class="loading-indicator">Laden...</div>
    <div v-else>
      <VueApexCharts
        type="line"
        height="350"
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// Typdefinitionen
interface DataPoint {
  date: string;
  value: number;
}

interface Props {
  currentData: DataPoint[];
  comparisonData?: DataPoint[] | null;
  comparisonLabel?: string;
}

// Props definieren
const props = withDefaults(defineProps<Props>(), {
  comparisonData: null,
  comparisonLabel: 'Vorjahr'
});

// Reaktive Zustände
const loading = ref(false);

// Berechnete Eigenschaften
const series = computed(() => {
  const result = [
    {
      name: 'Aktueller Zeitraum',
      data: props.currentData.map(item => item.value)
    }
  ];

  if (props.comparisonData) {
    result.push({
      name: props.comparisonLabel,
      data: props.comparisonData.map(item => item.value)
    });
  }

  return result;
});

const chartOptions = computed(() => {
  return {
    chart: {
      type: 'line',
      zoom: { enabled: false },
      toolbar: { show: false },
      fontFamily: 'Arial, sans-serif',
      background: 'transparent'
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      curve: 'smooth',
      width: 3,
      lineCap: 'round'
    },
    colors: ['#4285F4', '#34A853'],
    grid: {
      borderColor: '#e0e0e0',
      row: {
        colors: ['#f8f8f8', 'transparent'],
        opacity: 0.5
      }
    },
    xaxis: {
      categories: props.currentData.map(item => {
        // Formatierung des Datums (YYYY-MM zu MMM YYYY)
        const [year, month] = item.date.split('-');
        const date = new Date(Number(year), parseInt(month) - 1);
        return date.toLocaleDateString('de-DE', { month: 'short', year: 'numeric' });
      }),
      labels: {
        style: {
          colors: '#616161',
          fontSize: '12px'
        }
      },
      title: {
        text: 'Datum',
        style: {
          fontSize: '14px',
          fontWeight: 'normal',
          color: '#616161'
        }
      }
    },
    yaxis: {
      title: {
        text: 'Anzahl Beratungen',
        style: {
          fontSize: '14px',
          fontWeight: 'normal',
          color: '#616161'
        }
      },
      labels: {
        style: {
          colors: '#616161',
          fontSize: '12px'
        },
        formatter: (value: number) => Math.round(value)
      }
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: function(value: number) {
          return value.toFixed(0);
        }
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      floating: false,
      offsetY: -10,
      fontSize: '13px',
      fontFamily: 'Arial, sans-serif'
    },
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          height: 250
        },
        legend: {
          position: 'bottom'
        }
      }
    }]
  };
});

// Methoden
const calculateGrowth = (): number | null => {
  if (!props.comparisonData || props.currentData.length === 0 || props.comparisonData.length === 0) {
    return null;
  }

  // Summe aller Werte im aktuellen Zeitraum
  const currentSum = props.currentData.reduce((sum, item) => sum + item.value, 0);
  // Summe aller Werte im Vergleichszeitraum
  const comparisonSum = props.comparisonData.reduce((sum, item) => sum + item.value, 0);

  // Prozentuales Wachstum berechnen
  return comparisonSum === 0 ? 100 : ((currentSum - comparisonSum) / comparisonSum) * 100;
};
</script>

<style scoped>
.line-chart-container {
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
