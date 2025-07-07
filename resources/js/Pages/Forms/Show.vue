<script setup lang="ts">
import FormRenderer from '@/components/FormBuilder/FormRenderer.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { route } from 'ziggy-js';
import NoLayout from '@/layouts/NoLayout.vue';
import { isIframe } from '@/helpers';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout
});


const props = defineProps<{
  formDefinition: App.Data.FormDefinitionData;
}>();

</script>
<template>
    <div class="flex items-center justify-center flex-col">
    <Card class="w-200">
        <CardContent>
            <FormRenderer
                :form-definition="props.formDefinition"
                :submit-url="route('form.submit', props.formDefinition.id)"
                method="post"
            />
        </CardContent>
    </Card>
    </div>

</template>
