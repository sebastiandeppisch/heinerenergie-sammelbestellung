<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { router } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, MailQuestion } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    status: 'pending' | 'already_confirmed' | 'expired' | 'invalid';
    formName: string | null;
    token: string | null;
}>();

const processing = ref(false);

function confirm() {
    if (props.token === null) {
        return;
    }

    router.post(
        route('form.confirm', props.token),
        {},
        {
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        },
    );
}
</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent>
                <div class="flex flex-col items-center justify-center gap-4 text-center">
                    <template v-if="status === 'pending'">
                        <MailQuestion :size="96" class="text-muted-foreground" />
                        <div class="font-semibold">Bitte bestätige deine E-Mail-Adresse</div>
                        <p v-if="formName" class="text-sm text-muted-foreground">Für deine Einsendung „{{ formName }}“.</p>
                        <Button :disabled="processing" @click="confirm">E-Mail-Adresse bestätigen</Button>
                    </template>
                    <template v-else-if="status === 'already_confirmed'">
                        <CircleCheck :size="96" style="color: #00a651" />
                        <div class="font-semibold">Deine E-Mail-Adresse ist bereits bestätigt.</div>
                        <p class="text-sm text-muted-foreground">Du musst nichts weiter tun.</p>
                    </template>
                    <template v-else-if="status === 'expired'">
                        <CircleAlert :size="96" class="text-destructive" />
                        <div class="font-semibold">Der Bestätigungslink ist abgelaufen.</div>
                        <p class="text-sm text-muted-foreground">Bitte fülle das Formular erneut aus.</p>
                    </template>
                    <template v-else>
                        <CircleAlert :size="96" class="text-destructive" />
                        <div class="font-semibold">Dieser Bestätigungslink ist ungültig.</div>
                        <p class="text-sm text-muted-foreground">Prüfe, ob du den Link vollständig aus der E-Mail übernommen hast.</p>
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
