<template>
    <div class="kpi-dashboard">
        <div class="dashboard-grid">
            <!-- Area chart: Advice by status (left side) -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Beratungen nach Status</h2>
                    <div class="card-filter">
                        <select v-model="selectedTimeRange" @change="updateCharts">
                            <option value="month">Letzter Monat</option>
                            <option value="quarter">Letztes Quartal</option>
                            <option value="year">Letztes Jahr</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <area-chart :data="statusDistributionData" />
                </div>
            </div>

            <!-- Line chart: Advice per month (right side) -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Beratungen pro Monat</h2>
                    <div class="card-filter">
                        <select v-model="comparisonType" @change="updateCharts">
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
                        <select v-model="selectedResultFilter" @change="updateStatusFilter">
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
import AreaChart from './charts/AreaChart.vue';
import DonutChart from './charts/DonutChart.vue';
import LineChart from './charts/LineChart.vue';

// Type definitions
type TimeRange = 'month' | 'quarter' | 'year';
type ComparisonType = 'none' | 'lastYear' | 'lastPeriod';
type ResultFilter = 'in_progress' | 'completed' | 'unsuccessful' | 'all';

interface DataPoint {
    date: string;
    value: number;
}

interface StatusData {
    date: string;
    statusCounts: Record<string, number>;
}

interface CurrentStatusData {
    statusCounts: Record<string, number>;
}

interface AdviceCountData {
    currentData: DataPoint[];
    comparisonData: DataPoint[];
}

// Reactive variables
const selectedTimeRange = ref<TimeRange>('month');
const comparisonType = ref<ComparisonType>('lastYear');
const selectedResultFilter = ref<ResultFilter>('in_progress');

// Example data for line chart
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

// Example data for area chart
const statusDistributionData = ref<StatusData[]>([
    {
        date: '2023-01-01',
        statusCounts: {
            Neu: 20,
            'In Bearbeitung': 35,
            'Erfolgreich beraten': 15,
            'Nicht erfolgreich': 5,
        },
    },
    {
        date: '2023-02-01',
        statusCounts: {
            Neu: 15,
            'In Bearbeitung': 40,
            'Erfolgreich beraten': 22,
            'Nicht erfolgreich': 8,
        },
    },
    {
        date: '2023-03-01',
        statusCounts: {
            Neu: 18,
            'In Bearbeitung': 45,
            'Erfolgreich beraten': 28,
            'Nicht erfolgreich': 10,
        },
    },
    {
        date: '2023-04-01',
        statusCounts: {
            Neu: 22,
            'In Bearbeitung': 42,
            'Erfolgreich beraten': 35,
            'Nicht erfolgreich': 12,
        },
    },
    {
        date: '2023-05-01',
        statusCounts: {
            Neu: 25,
            'In Bearbeitung': 48,
            'Erfolgreich beraten': 38,
            'Nicht erfolgreich': 15,
        },
    },
    {
        date: '2023-06-01',
        statusCounts: {
            Neu: 30,
            'In Bearbeitung': 52,
            'Erfolgreich beraten': 42,
            'Nicht erfolgreich': 18,
        },
    },
]);

// Example data for donut chart
const currentStatusData = ref<CurrentStatusData>({
    statusCounts: {
        Neu: 28,
        'In Bearbeitung': 42,
        'Erfolgreich beraten': 65,
        'Nicht erfolgreich': 13,
    },
});

// Filtered status data based on selectedResultFilter
const filteredStatusData = computed(() => {
    if (selectedResultFilter.value === 'all') {
        return currentStatusData.value;
    }

    const statusMapping = {
        in_progress: 'In Bearbeitung',
        completed: 'Erfolgreich beraten',
        unsuccessful: 'Nicht erfolgreich',
    };

    const filteredStatus = statusMapping[selectedResultFilter.value];
    const filtered = { statusCounts: {} };

    // Only include the selected status and 'Neu' for context
    // @ts-ignore
    filtered.statusCounts['Neu'] = currentStatusData.value.statusCounts['Neu'];
    // @ts-ignore
    filtered.statusCounts[filteredStatus] = currentStatusData.value.statusCounts[filteredStatus];

    return filtered;
});

// Update charts based on time range selection
const updateCharts = () => {
    // In a real scenario, data would be loaded from the backend
    console.log(`Zeitraum: ${selectedTimeRange.value}, Vergleich: ${comparisonType.value}`);

    // Here as an example - for real implementation, an API call would be made
    if (selectedTimeRange.value === 'quarter') {
        // Example update of data for quarterly view
        adviceCountData.value.currentData = adviceCountData.value.currentData.filter((_, index) => index % 3 === 0);
        adviceCountData.value.comparisonData = adviceCountData.value.comparisonData.filter((_, index) => index % 3 === 0);
        statusDistributionData.value = statusDistributionData.value.filter((_, index) => index % 2 === 0);
    } else if (selectedTimeRange.value === 'year') {
        // A year overview could have fewer data points
        adviceCountData.value.currentData = adviceCountData.value.currentData.filter((_, index) => index % 4 === 0);
        adviceCountData.value.comparisonData = adviceCountData.value.comparisonData.filter((_, index) => index % 4 === 0);
        statusDistributionData.value = [statusDistributionData.value[0], statusDistributionData.value[statusDistributionData.value.length - 1]];
    }
};

// Update status filter
const updateStatusFilter = () => {
    console.log(`Status filter: ${selectedResultFilter.value}`);
    // In a real scenario, this might trigger additional data loading or filtering
};
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

/* Update the status distribution card to be half-width and positioned on the left */
.status-distribution-card {
    grid-column: 1 / 2; /* Change from 1/-1 (full width) to 1/2 (left half) */
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

    /* Ensure the status distribution card is also full width on smaller screens */
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
