<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    mapPointId: string;
    linkRequested: boolean;
}>();

const form = useForm({});

function requestLink() {
    form.post(route('map-contact.unsubscribe.request', props.mapPointId));
}
</script>

<template>
    <div class="mx-auto w-full max-w-2xl">
        <Card>
            <CardHeader>
                <CardTitle>Keine Kontaktanfragen mehr erhalten</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="linkRequested">
                    Falls für diesen Eintrag eine Kontaktadresse hinterlegt ist, haben wir dorthin einen Link geschickt. Über ihn kannst du
                    Kontaktanfragen abschalten.
                </p>
                <template v-else>
                    <p>
                        Wir schicken dir einen Link an die hinterlegte Kontaktadresse. Über ihn kannst du Kontaktanfragen zu diesem Eintrag abschalten
                        und später wieder einschalten.
                    </p>
                    <Button :disabled="form.processing" @click="requestLink">Link anfordern</Button>
                </template>
            </CardContent>
        </Card>
    </div>
</template>
