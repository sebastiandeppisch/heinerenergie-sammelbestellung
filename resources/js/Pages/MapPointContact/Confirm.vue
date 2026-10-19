<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    status: 'pending' | 'forwarded' | 'unavailable' | 'already_confirmed' | 'expired' | 'invalid';
    token: string | null;
    title: string | null;
}>();

const processing = ref(false);

function confirm() {
    if (props.token === null) {
        return;
    }

    router.post(
        route('map-contact.confirm', props.token),
        {},
        {
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        },
    );
}
</script>

<template>
    <div class="mx-auto w-full max-w-2xl">
        <Card>
            <CardHeader>
                <CardTitle>
                    <template v-if="status === 'pending'">Kontaktanfrage bestätigen</template>
                    <template v-else-if="status === 'forwarded'">Danke, deine Nachricht ist unterwegs</template>
                    <template v-else-if="status === 'unavailable'">Kontakt nicht mehr möglich</template>
                    <template v-else-if="status === 'already_confirmed'">Bereits bestätigt</template>
                    <template v-else-if="status === 'expired'">Link abgelaufen</template>
                    <template v-else>Link ungültig</template>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <template v-if="status === 'pending'">
                    <p>Bestätige deine E-Mail-Adresse, damit wir deine Nachricht zum Eintrag „{{ title }}“ weiterleiten.</p>
                    <Button :disabled="processing" @click="confirm">Kontaktanfrage bestätigen</Button>
                </template>
                <p v-else-if="status === 'forwarded'">
                    Wir haben deine Nachricht zum Eintrag „{{ title }}“ weitergeleitet. Wenn die Person antwortet, bekommst du eine E-Mail.
                </p>
                <p v-else-if="status === 'unavailable'">Die Verantwortlichen dieses Eintrags nehmen gerade keine Kontaktanfragen an.</p>
                <p v-else-if="status === 'already_confirmed'">Diese Kontaktanfrage wurde schon bestätigt. Wir leiten sie nicht noch einmal weiter.</p>
                <p v-else-if="status === 'expired'">Der Bestätigungslink ist abgelaufen. Bitte schicke deine Nachricht über die Karte erneut ab.</p>
                <p v-else>Dieser Bestätigungslink ist ungültig.</p>
            </CardContent>
        </Card>
    </div>
</template>
