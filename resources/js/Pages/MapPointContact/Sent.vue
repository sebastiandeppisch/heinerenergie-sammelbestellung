<script setup lang="ts">
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, MailCheck } from '@lucide/vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

defineProps<{
    mapEmbedId: string;
    expiresHours: number;
}>();

useAutoResizeIframeIfIsIframe();
</script>

<template>
    <div class="mx-auto w-full max-w-2xl space-y-4">
        <Card>
            <CardHeader class="items-center text-center">
                <MailCheck :size="64" class="text-primary" />
                <CardTitle>Bitte bestätige deine E-Mail-Adresse</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-center">
                <p>Wir haben dir eine E-Mail mit einem Bestätigungslink geschickt. Erst nach deinem Klick leiten wir deine Nachricht weiter.</p>
                <p class="text-sm text-muted-foreground">Der Link ist {{ expiresHours }} Stunden gültig. Schau bitte auch im Spam-Ordner nach.</p>
            </CardContent>
        </Card>

        <Link :href="route('map.public', mapEmbedId)" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:underline">
            <ArrowLeft class="h-4 w-4" />
            Zurück zur Karte
        </Link>
    </div>
</template>
