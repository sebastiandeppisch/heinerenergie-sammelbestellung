<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    formDefinition: App.Data.FormDefinitionData;
    // embedded: boolean;
}>();
</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-200">
            <CardContent>
                <div class="flex flex-col items-center justify-center">
                    <div class="font-semibold">
                        <!-- TODO  add submission text-->
                        Vielen Dank für Deine Anfrage
                    </div>
                    <div style="margin-top: 32px">
                        <font-awesome-icon icon="circle-check" size="6x" style="color: #00a651" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
