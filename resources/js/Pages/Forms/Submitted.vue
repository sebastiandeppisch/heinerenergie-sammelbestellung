<script setup lang="ts">
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { router, usePage } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import { computed, defineAsyncComponent, type Component } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = withDefaults(
    defineProps<{
        formDefinition: App.Data.FormDefinitionData;
        notices?: App.Data.FormTargetNoticeData[];
    }>(),
    {
        notices: () => [],
    },
);

useAutoResizeIframeIfIsIframe();

/** Optional plugin component: the glob is empty when the HexaNetz plugin is removed. */
const highlightLoaders = import.meta.glob<{ default: Component }>('@/components/HexNet/HexNetHighlight.vue');
const HexNetHighlight = Object.values(highlightLoaders)[0] ? defineAsyncComponent(Object.values(highlightLoaders)[0]) : null;
const hexNetHighlight = computed(() =>
    HexNetHighlight ? (usePage().props.hexNetHighlight as App.HexNet.Data.HexNetHighlightData | undefined) : undefined,
);

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
                        <CircleCheck :size="96" style="color: #00a651" />
                    </div>
                    <div v-if="hexNetHighlight" class="mt-8 w-full">
                        <HexNetHighlight :highlight="hexNetHighlight" />
                    </div>
                    <div v-if="notices.length > 0" class="mt-8 w-full space-y-4" data-test="form-target-notices">
                        <div v-for="(notice, index) in notices" :key="index" class="rounded-md border p-4">
                            <div class="font-semibold">{{ notice.title }}</div>
                            <p v-if="notice.text" class="mt-1 text-sm">{{ notice.text }}</p>
                            <Button v-if="notice.url && notice.url !== hexNetHighlight?.follow_up_url" as-child class="mt-3">
                                <a :href="notice.url" data-test="form-target-notice-link">{{ notice.url_label || notice.url }}</a>
                            </Button>
                        </div>
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
