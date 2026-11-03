<script setup lang="ts">
import FormRenderer from '@/components/FormBuilder/FormRenderer.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    token: string;
    /** The proof of a link from our mail, kept on the way back to the manage page. */
    proof: string | null;
    /** The title of the point the form adds to. */
    title: string;
    formDefinition: App.Data.FormDefinitionData;
    initialData: Record<string, unknown>;
    storedImages: Record<string, App.Data.ImageData[]>;
}>();

const params = computed(() => (props.proof ? { token: props.token, nachweis: props.proof } : { token: props.token }));
</script>

<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200 min-w-50">
            <CardContent class="flex flex-col gap-4">
                <p class="text-sm text-muted-foreground">
                    Dein Eintrag „{{ title }}“ ·
                    <Link :href="route('map-point.manage.show', params)" class="underline">Eintrag verwalten</Link>
                </p>
                <FormRenderer
                    :form-definition="formDefinition"
                    :submit-url="route('map-point.manage.follow-up.submit', params)"
                    :initial-data="initialData"
                    :stored-images="storedImages"
                />
            </CardContent>
        </Card>
    </div>
</template>
