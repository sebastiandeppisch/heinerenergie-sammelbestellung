<script setup lang="ts">
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/components/ui/accordion';
import Card from '@/shadcn/components/ui/card/Card.vue';
import Setting from './../components/Setting.vue';

const props = defineProps<{
    settings: any[];
}>();
</script>

<template>
    <div>
        <h2 class="content-block">Einstellungen</h2>
        <div style="margin: 30px 40px 30px 40px">
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
    </div>
</template>
