<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/components/ui/accordion';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { setLayoutProps } from '@inertiajs/vue3';
import Setting from './../components/Setting.vue';

const props = defineProps<{
    settings: any[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Einstellungen' }],
});
</script>

<template>
    <div class="mx-auto w-full max-w-3xl">
        <PageHeader title="Einstellungen" />
        <Card>
            <Accordion type="single" collapsible class="w-full pr-4 pl-4">
                <AccordionItem v-for="setting in props.settings" :key="setting.id" :value="String(setting.id)">
                    <AccordionTrigger>
                        <div style="font-size: 16pt">{{ setting.name }}</div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <Setting :setting="setting" />
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </Card>
    </div>
</template>
