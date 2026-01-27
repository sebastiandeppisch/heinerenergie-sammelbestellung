<script setup lang="ts">
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    formDefinition: App.Data.FormDefinitionData;
    // embedded: boolean;
}>();

useAutoResizeIframeIfIsIframe();

const successMessage = computed(() => {
    return props.formDefinition.success_message || 'Vielen Dank für Deine Anfrage';
});

function handleNextFormButton() {
    // Navigate back to the form (clears the form)
    router.visit(route('form.show', props.formDefinition.id), {
        method: 'get',
        preserveState: false,
        preserveScroll: false,
    });
}
</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-200">
            <CardContent>
                <div class="flex flex-col items-center justify-center">
                    <div class="font-semibold">
                        {{ successMessage }}
                    </div>
                    <div style="margin-top: 32px">
                        <font-awesome-icon icon="circle-check" size="6x" style="color: #00a651" />
                    </div>
                    <div v-if="formDefinition.show_next_form_button" style="margin-top: 32px">
                        <Button @click="handleNextFormButton" variant="default">
                            {{ formDefinition.next_form_button_text || 'Nächstes Formular' }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
