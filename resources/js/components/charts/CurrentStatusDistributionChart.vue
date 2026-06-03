<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between border-b">
            <CardTitle>Aktuelle Statusverteilung</CardTitle>
            <Select v-model="selectedResult">
                <SelectTrigger class="w-48">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Alle Status</SelectItem>
                    <SelectItem value="0">Neu</SelectItem>
                    <SelectItem value="1">In Bearbeitung</SelectItem>
                    <SelectItem value="2">Erfolgreich beraten</SelectItem>
                    <SelectItem value="3">Nicht erfolgreich</SelectItem>
                </SelectContent>
            </Select>
        </CardHeader>
        <CardContent class="min-h-[350px] pt-4">
            <Skeleton v-if="isLoading" class="h-[350px] w-full" />
            <VueApexCharts v-else type="donut" height="350" :options="chartOptions" :series="series" />
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Skeleton } from '@/shadcn/components/ui/skeleton';
import type { ApexOptions } from 'apexcharts';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const selectedResult = ref<string>('all');
const isLoading = ref(false);
const allData = ref<App.Data.StatusNameCountData[]>([]);

const filtered = computed(() => {
    if (selectedResult.value === 'all') {
        return allData.value;
    }
    const r = parseInt(selectedResult.value) as App.Enums.AdviceStatusResult;
    return allData.value.filter((d) => d.result === r);
});

const series = computed(() => filtered.value.map((d) => d.count));
const labels = computed(() => filtered.value.map((d) => d.name));

const chartOptions = computed(
    (): ApexOptions => ({
        chart: {
            type: 'donut',
            fontFamily: 'Arial, sans-serif',
            background: 'transparent',
        },
        labels: labels.value,
        colors: ['#FBBC05', '#4285F4', '#34A853', '#EA4335', '#8B5CF6', '#F97316', '#06B6D4'],
        legend: {
            position: 'bottom',
            fontSize: '13px',
            formatter: (name: string, opts: any) => `${name} – ${opts.w.globals.series[opts.seriesIndex]}`,
        },
        dataLabels: {
            enabled: true,
            formatter: (_val: number, opts: any) => `${opts.w.globals.series[opts.seriesIndex]} (${Math.round(_val)}%)`,
            style: { fontSize: '12px' },
            dropShadow: { enabled: false },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Gesamt',
                            fontSize: '16px',
                            fontWeight: 600,
                            formatter: (w: any) => w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0),
                        },
                    },
                },
            },
        },
        stroke: { width: 0 },
        tooltip: { y: { formatter: (v: number) => String(v) } },
    }),
);

async function loadData() {
    isLoading.value = true;
    try {
        const response = await axios.get<App.Data.StatusNameCountData[]>(route('api.kpi.current-status-distribution'));
        allData.value = response.data;
    } finally {
        isLoading.value = false;
    }
}

onMounted(loadData);
</script>
