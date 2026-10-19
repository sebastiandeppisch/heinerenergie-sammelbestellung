<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { CircleAlert, CircleCheck, MailCheck } from '@lucide/vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

defineProps<{
    status: 'sent' | 'invalid' | 'deleted';
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent>
                <div class="flex flex-col items-center justify-center gap-4 text-center">
                    <template v-if="status === 'sent'">
                        <MailCheck :size="96" class="text-muted-foreground" />
                        <div class="font-semibold">Falls es Einträge gibt, haben wir dir eine E-Mail geschickt.</div>
                        <p class="text-sm text-muted-foreground">Der Link darin ist einige Tage gültig. Ältere Links funktionieren nicht mehr.</p>
                    </template>
                    <template v-else-if="status === 'deleted'">
                        <CircleCheck :size="96" style="color: #00a651" />
                        <div class="font-semibold">Dein Eintrag ist gelöscht.</div>
                        <p class="text-sm text-muted-foreground">Wir haben den Eintrag und deine Angaben aus dem Formular endgültig entfernt.</p>
                    </template>
                    <template v-else>
                        <CircleAlert :size="96" class="text-destructive" />
                        <div class="font-semibold">Dieser Link ist ungültig oder abgelaufen.</div>
                        <p class="text-sm text-muted-foreground">
                            Du kannst einen neuen Link anfordern:
                            <a :href="route('map-point.manage.request')" class="underline">Eintrag verwalten</a>
                        </p>
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
