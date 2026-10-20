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
    token: string | null;
    title: string | null;
    contactEnabled: boolean | null;
}>();

const form = useForm({});

function setEnabled(enabled: boolean) {
    if (props.token === null) {
        return;
    }

    form.post(route(enabled ? 'map-contact.manage.enable' : 'map-contact.manage.disable', props.token));
}
</script>

<template>
    <div class="mx-auto w-full max-w-2xl">
        <Card>
            <CardHeader>
                <CardTitle>{{ token ? `Kontaktanfragen zu „${title}“` : 'Link ungültig' }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <template v-if="token">
                    <template v-if="contactEnabled">
                        <p>Kontaktanfragen über die Karte sind eingeschaltet.</p>
                        <Button variant="destructive" :disabled="form.processing" @click="setEnabled(false)">Kontaktanfragen abschalten</Button>
                    </template>
                    <template v-else>
                        <p>Kontaktanfragen über die Karte sind abgeschaltet. Du bekommst keine weiteren Nachrichten.</p>
                        <Button :disabled="form.processing" @click="setEnabled(true)">Wieder einschalten</Button>
                    </template>
                </template>
                <p v-else>Dieser Link ist ungültig oder abgelaufen. Bitte fordere über den Link in einer Kontaktanfrage einen neuen an.</p>
            </CardContent>
        </Card>
    </div>
</template>
