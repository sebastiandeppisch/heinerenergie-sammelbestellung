<script setup lang="ts">
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import CardHeader from '@/shadcn/components/ui/card/CardHeader.vue';
const props = defineProps<{
    advisorInfo: string;
}>();
</script>

<template>
    <div ref="outer">
        <h2 class="content-block">Dashboard</h2>
        <Card style="margin: 2rem">
            <CardHeader>
                <h2>Berater*innen-Infos</h2>
            </CardHeader>
            <CardContent>
                <div v-html="props.advisorInfo"></div>
            </CardContent>
        </Card>

        <!-- <KpiDashboard /> -->
    </div>
</template>
