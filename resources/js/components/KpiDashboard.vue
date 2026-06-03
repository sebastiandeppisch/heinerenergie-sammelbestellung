<template>
    <div class="kpi-dashboard">
        <div class="dashboard-grid">
            <AdviceStatusDistributionChart />

            <!-- Line chart: Advice per month (right side) -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Beratungen pro Monat</h2>
                    <div class="card-filter">
                        <select v-model="comparisonType">
                            <option value="none">Kein Vergleich</option>
                            <option value="lastYear">Vorjahr</option>
                            <option value="lastPeriod">Vorperiode</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <line-chart
                        :current-data="adviceCountData.currentData"
                        :comparison-data="comparisonType !== 'none' ? adviceCountData.comparisonData : null"
                        :comparison-label="comparisonType === 'lastYear' ? 'Vorjahr' : 'Vorperiode'"
                    />
                </div>
            </div>

            <!-- Donut chart: Current status distribution (half width, on left) -->
            <div class="dashboard-card status-distribution-card">
                <div class="card-header">
                    <h2>Aktuelle Statusverteilung</h2>
                    <div class="card-filter">
                        <select v-model="selectedResultFilter">
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="completed">Erfolgreich beraten</option>
                            <option value="unsuccessful">Nicht erfolgreich</option>
                            <option value="all">Alle Status</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <donut-chart :data="filteredStatusData" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import DonutChart from './charts/DonutChart.vue';
import LineChart from './charts/LineChart.vue';
import AdviceStatusDistributionChart from './charts/AdviceStatusDistributionChart.vue';

type ComparisonType = 'none' | 'lastYear' | 'lastPeriod';
type ResultFilter = 'in_progress' | 'completed' | 'unsuccessful' | 'all';

interface DataPoint {
    date: string;
    value: number;
}

interface CurrentStatusData {
    statusCounts: Record<string, number>;
}

interface AdviceCountData {
    currentData: DataPoint[];
    comparisonData: DataPoint[];
}

// ── Beratungen pro Monat (line chart, mock data) ──────────────────────────────

const comparisonType = ref<ComparisonType>('lastYear');

const adviceCountData = ref<AdviceCountData>({
    currentData: [
        { date: '2023-01', value: 45 },
        { date: '2023-02', value: 52 },
        { date: '2023-03', value: 65 },
        { date: '2023-04', value: 58 },
        { date: '2023-05', value: 72 },
        { date: '2023-06', value: 80 },
        { date: '2023-07', value: 75 },
        { date: '2023-08', value: 82 },
        { date: '2023-09', value: 90 },
        { date: '2023-10', value: 85 },
        { date: '2023-11', value: 92 },
        { date: '2023-12', value: 88 },
    ],
    comparisonData: [
        { date: '2022-01', value: 40 },
        { date: '2022-02', value: 48 },
        { date: '2022-03', value: 52 },
        { date: '2022-04', value: 55 },
        { date: '2022-05', value: 60 },
        { date: '2022-06', value: 65 },
        { date: '2022-07', value: 62 },
        { date: '2022-08', value: 70 },
        { date: '2022-09', value: 75 },
        { date: '2022-10', value: 72 },
        { date: '2022-11', value: 80 },
        { date: '2022-12', value: 78 },
    ],
});

// ── Aktuelle Statusverteilung (donut chart, mock data) ────────────────────────

const selectedResultFilter = ref<ResultFilter>('in_progress');

const currentStatusData = ref<CurrentStatusData>({
    statusCounts: {
        Neu: 28,
        'In Bearbeitung': 42,
        'Erfolgreich beraten': 65,
        'Nicht erfolgreich': 13,
    },
});

const filteredStatusData = computed(() => {
    if (selectedResultFilter.value === 'all') {
        return currentStatusData.value;
    }

    const statusMapping: Record<string, string> = {
        in_progress: 'In Bearbeitung',
        completed: 'Erfolgreich beraten',
        unsuccessful: 'Nicht erfolgreich',
    };

    const filteredStatus = statusMapping[selectedResultFilter.value];
    return {
        statusCounts: {
            Neu: currentStatusData.value.statusCounts['Neu'],
            [filteredStatus]: currentStatusData.value.statusCounts[filteredStatus],
        },
    };
});
</script>

<style scoped>
.kpi-dashboard {
    padding: 20px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: auto auto;
    gap: 20px;
}

.status-distribution-card {
    grid-column: 1 / 2;
}

.dashboard-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.card-filter select {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

.card-body {
    padding: 20px;
    min-height: 300px;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .status-distribution-card {
        grid-column: 1;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .card-filter {
        margin-top: 10px;
    }
}
</style>
