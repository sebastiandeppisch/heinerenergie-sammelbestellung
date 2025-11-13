<script setup lang="ts">
import FormRenderer from '@/components/FormBuilder/FormRenderer.vue';
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    formDefinition: App.Data.FormDefinitionData;
}>();

useAutoResizeIframeIfIsIframe();

</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="max-w-200 w-full min-w-50">
            <CardContent>
                <FormRenderer :form-definition="props.formDefinition" :submit-url="route('form.submit', props.formDefinition.id)" method="post" />
            </CardContent>
        </Card>
    </div>
</template>
