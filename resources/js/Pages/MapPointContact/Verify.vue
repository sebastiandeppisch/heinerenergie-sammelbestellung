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
    status: 'pending' | 'verified' | 'expired' | 'invalid';
    token: string | null;
    title: string | null;
    contactEnabled: boolean | null;
}>();

const processing = ref(false);

function verify() {
    if (props.token === null) {
        return;
    }

    router.post(
        route('map-contact.verify', props.token),
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
                    <template v-if="status === 'pending'">Kontaktadresse bestätigen</template>
                    <template v-else-if="status === 'verified'">Kontaktadresse bestätigt</template>
                    <template v-else-if="status === 'expired'">Link abgelaufen</template>
                    <template v-else>Link ungültig</template>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <template v-if="status === 'pending'">
                    <p>Bestätige, dass Besucher:innen der Karte dir zum Eintrag „{{ title }}“ über ein Formular schreiben dürfen.</p>
                    <Button :disabled="processing" @click="verify">Kontaktadresse bestätigen</Button>
                </template>
                <template v-else-if="status === 'verified'">
                    <p>Danke! Deine Adresse ist für den Eintrag „{{ title }}“ bestätigt.</p>
                    <p v-if="contactEnabled">Sobald der Eintrag veröffentlicht ist, können dir Besucher:innen der Karte schreiben.</p>
                    <p v-else>Kontaktanfragen sind für diesen Eintrag aber nicht eingeschaltet.</p>
                </template>
                <p v-else-if="status === 'expired'">Der Link ist abgelaufen. Bitte wende dich an die Initiative, um einen neuen zu bekommen.</p>
                <p v-else>Dieser Link ist ungültig oder wurde bereits verwendet.</p>
            </CardContent>
        </Card>
    </div>
</template>
