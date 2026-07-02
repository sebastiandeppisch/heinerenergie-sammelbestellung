<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between border-b">
            <CardTitle>Beratungen pro Monat</CardTitle>
            <Select v-model="selectedYears">
                <SelectTrigger class="w-36">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="1">1 Jahr</SelectItem>
                    <SelectItem value="2">2 Jahre</SelectItem>
                    <SelectItem value="3">3 Jahre</SelectItem>
                    <SelectItem value="4">4 Jahre</SelectItem>
                    <SelectItem value="5">5 Jahre</SelectItem>
                </SelectContent>
            </Select>
        </CardHeader>
        <CardContent class="min-h-[350px] pt-4">
            <Skeleton v-if="isLoading" class="h-[350px] w-full" />
            <VueApexCharts v-else type="line" height="350" :options="chartOptions" :series="series" />
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Skeleton } from '@/shadcn/components/ui/skeleton';
import type { ApexOptions } from 'apexcharts';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { route } from 'ziggy-js';

const COLORS = ['#4285F4', '#EA4335', '#34A853', '#FBBC05', '#8B5CF6'];

const selectedYears = ref('2');
const isLoading = ref(false);
const monthLabels = ref<string[]>([]);
const seriesData = ref<App.Data.YearlyAdviceCountData[]>([]);

watch(selectedYears, loadData);

const series = computed(() =>
    seriesData.value.map((s) => ({
        name: s.label,
        data: s.counts,
    })),
);

const chartOptions = computed(
    (): ApexOptions => ({
        chart: {
            type: 'line',
            zoom: { enabled: false },
            toolbar: { show: false },
            fontFamily: 'Arial, sans-serif',
            background: 'transparent',
        },
        stroke: { curve: 'smooth', width: 3 },
        colors: COLORS,
        dataLabels: { enabled: false },
        grid: { borderColor: '#e0e0e0' },
        xaxis: {
            categories: monthLabels.value,
            labels: { style: { colors: '#616161', fontSize: '12px' } },
        },
        yaxis: {
            title: {
                text: 'Anzahl Beratungen',
                style: { fontSize: '14px', fontWeight: 'normal', color: '#616161' },
            },
            labels: {
                style: { colors: '#616161', fontSize: '12px' },
                formatter: (v: number) => String(Math.round(v)),
            },
        },
        tooltip: { shared: true, intersect: false },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' },
    }),
);

async function loadData() {
    isLoading.value = true;
    try {
        const response = await axios.get<{ series: App.Data.YearlyAdviceCountData[]; monthLabels: string[] }>(route('api.kpi.monthly-count'), {
            params: { years: selectedYears.value },
        });
        seriesData.value = response.data.series;
        monthLabels.value = response.data.monthLabels;
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadData);
</script>
