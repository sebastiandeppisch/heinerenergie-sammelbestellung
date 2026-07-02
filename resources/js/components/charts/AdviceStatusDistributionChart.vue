<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b">
            <CardTitle>Beratungen nach Status</CardTitle>
            <div class="flex flex-col items-end gap-2">
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

                <div v-if="isCustomMode" class="flex flex-wrap items-center justify-end gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs whitespace-nowrap text-muted-foreground">Von</span>
                        <DatePickerInput v-model="customFrom" :max="customTo" @update:model-value="loadData" />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs whitespace-nowrap text-muted-foreground">Bis</span>
                        <DatePickerInput v-model="customTo" :min="customFrom" :max="today" @update:model-value="loadData" />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs whitespace-nowrap text-muted-foreground">Aggregation</span>
                        <Select
                            :model-value="customAggregation"
                            @update:model-value="
                                (v) => {
                                    customAggregation = v as App.Enums.Aggregation;
                                    loadData();
                                }
                            "
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
        </CardHeader>
        <CardContent class="min-h-[300px] pt-4">
            <Skeleton v-if="isLoading" class="h-[300px] w-full" />
            <AreaChart v-else :data="chartData" />
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import DatePickerInput from '@/shadcn/components/ui/date-picker/DatePickerInput.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Skeleton } from '@/shadcn/components/ui/skeleton';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import AreaChart from './AreaChart.vue';
import { route } from 'ziggy-js';

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
        case 'week':
            return { from: formatDate(subDays(now, 7)), to: today, aggregation: 'day' };
        case 'month':
            return { from: formatDate(subMonths(now, 1)), to: today, aggregation: 'week' };
        case 'quarter':
            return { from: formatDate(subMonths(now, 3)), to: today, aggregation: 'month' };
        case 'year':
            return { from: formatDate(subMonths(now, 12)), to: today, aggregation: 'month' };
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
