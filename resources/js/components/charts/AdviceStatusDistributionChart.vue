<template>
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Beratungen nach Status</h2>
            <div class="card-controls">
                <Select v-model="selectedPreset">
                    <SelectTrigger class="w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="week">Letzte Woche</SelectItem>
                        <SelectItem value="month">Letzter Monat</SelectItem>
                        <SelectItem value="quarter">Letztes Quartal</SelectItem>
                        <SelectItem value="year">Letztes Jahr</SelectItem>
                        <SelectItem value="custom">Benutzerdefiniert</SelectItem>
                    </SelectContent>
                </Select>

                <div v-if="isCustomMode" class="custom-controls">
                    <div class="custom-field">
                        <span class="custom-label">Von</span>
                        <DatePickerInput v-model="customFrom" :max="customTo" @update:model-value="loadData" />
                    </div>
                    <div class="custom-field">
                        <span class="custom-label">Bis</span>
                        <DatePickerInput v-model="customTo" :min="customFrom" :max="today" @update:model-value="loadData" />
                    </div>
                    <div class="custom-field">
                        <span class="custom-label">Aggregation</span>
                        <Select
                            :model-value="customAggregation"
                            @update:model-value="(v) => { customAggregation = v as App.Enums.Aggregation; loadData(); }"
                        >
                            <SelectTrigger class="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="day">Täglich</SelectItem>
                                <SelectItem value="week">Wöchentlich</SelectItem>
                                <SelectItem value="month">Monatlich</SelectItem>
                                <SelectItem value="quarter">Quartalsweise</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <Skeleton v-if="isLoading" class="w-full h-[300px]" />
            <AreaChart v-else :data="chartData" />
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import AreaChart from './AreaChart.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Skeleton } from '@/shadcn/components/ui/skeleton';
import DatePickerInput from '@/shadcn/components/ui/date-picker/DatePickerInput.vue';

type Preset = 'week' | 'month' | 'quarter' | 'year' | 'custom';

function formatDate(d: Date): string {
    return d.toISOString().slice(0, 10);
}

function subMonths(d: Date, n: number): Date {
    const r = new Date(d);
    r.setMonth(r.getMonth() - n);
    return r;
}

function subDays(d: Date, n: number): Date {
    const r = new Date(d);
    r.setDate(r.getDate() - n);
    return r;
}

const today = formatDate(new Date());

function presetParams(preset: Exclude<Preset, 'custom'>): { from: string; to: string; aggregation: App.Enums.Aggregation } {
    const now = new Date();
    switch (preset) {
        case 'week':    return { from: formatDate(subDays(now, 7)),    to: today, aggregation: 'day'   };
        case 'month':   return { from: formatDate(subMonths(now, 1)),  to: today, aggregation: 'week'  };
        case 'quarter': return { from: formatDate(subMonths(now, 3)),  to: today, aggregation: 'month' };
        case 'year':    return { from: formatDate(subMonths(now, 12)), to: today, aggregation: 'month' };
    }
}

const selectedPreset = ref<Preset>('year');
const isCustomMode = computed(() => selectedPreset.value === 'custom');

const initial = presetParams('year');
const customFrom = ref(initial.from);
const customTo = ref(initial.to);
const customAggregation = ref<App.Enums.Aggregation>(initial.aggregation);

const isLoading = ref(false);
const chartData = ref<App.Data.StatusDistributionPointData[]>([]);

watch(selectedPreset, (preset) => {
    if (preset !== 'custom') {
        const params = presetParams(preset);
        customFrom.value = params.from;
        customTo.value = params.to;
        customAggregation.value = params.aggregation;
    }
    loadData();
});

async function loadData() {
    isLoading.value = true;
    try {
        const response = await axios.get<App.Data.StatusDistributionPointData[]>(route('api.kpi.status-distribution'), {
            params: {
                from: customFrom.value,
                to: customTo.value,
                aggregation: customAggregation.value,
            },
        });
        chartData.value = response.data;
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadData);
</script>

<style scoped>
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
    align-items: flex-start;
    gap: 12px;
}

.card-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
    flex-shrink: 0;
    padding-top: 6px;
}

.card-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.custom-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    align-items: center;
}

.custom-field {
    display: flex;
    align-items: center;
    gap: 6px;
}

.custom-label {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
}

.card-body {
    padding: 20px;
    min-height: 300px;
}

@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .card-controls {
        align-items: flex-start;
        width: 100%;
    }

    .custom-controls {
        justify-content: flex-start;
    }
}
</style>
