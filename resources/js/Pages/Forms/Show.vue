<script setup lang="ts">
import FormRenderer from '@/components/FormBuilder/FormRenderer.vue';
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    formDefinition: App.Data.FormDefinitionData | null;
    formToken?: string;
    embedBlocked?: boolean;
}>();

useAutoResizeIframeIfIsIframe();

/** A pin given as ?lat=&lng= presets the first location field, e.g. from „Hier mitmachen“ on a HexaNetz. */
const initialData = computed<Record<string, unknown> | undefined>(() => {
    const params = new URLSearchParams(window.location.search);
    const lat = Number(params.get('lat'));
    const lng = Number(params.get('lng'));
    const field = props.formDefinition?.fields.find((f) => f.type === 'geo_coordinate');

    if (!field || !params.has('lat') || !params.has('lng') || !Number.isFinite(lat) || !Number.isFinite(lng)) {
        return undefined;
    }

    return { [field.id]: { lat, lng } };
});
</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200 min-w-50">
            <CardContent>
                <p v-if="props.embedBlocked" class="text-center text-muted-foreground">
                    Dieses Formular darf auf dieser Domain nicht eingebettet werden.
                </p>
                <FormRenderer
                    v-else-if="props.formDefinition"
                    :form-definition="props.formDefinition"
                    :submit-url="route('form.submit', props.formDefinition.id)"
                    :form-token="props.formToken"
                    :initial-data="initialData"
                    method="post"
                />
            </CardContent>
        </Card>
    </div>
</template>
