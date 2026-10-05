<script setup lang="ts">
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { MailCheck } from '@lucide/vue';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

defineProps<{
    formDefinition: App.Data.FormDefinitionData;
}>();

useAutoResizeIframeIfIsIframe();
</script>
<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent>
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="font-semibold">Fast geschafft, bitte bestätige deine E-Mail-Adresse.</div>
                    <p class="mt-4 text-sm text-muted-foreground">
                        Wir haben dir eine E-Mail mit einem Bestätigungslink geschickt. Erst nach der Bestätigung wird deine Einsendung bearbeitet.
                        Schau bitte auch in deinen Spam-Ordner.
                    </p>
                    <div style="margin-top: 32px">
                        <MailCheck :size="96" style="color: #00a651" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
