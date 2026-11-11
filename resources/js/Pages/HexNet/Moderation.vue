<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Head, useForm } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps<{
    point: App.HexNet.Data.HexNetModerationPointData;
    action: App.HexNet.Enums.HexNetModerationAction;
    actionLabel: string;
    confirmUrl: string | null;
    done: boolean;
}>();

const form = useForm({});

const question = computed(
    () =>
        ({
            freigeben: 'Soll dieser Trittstein auf der Karte erscheinen?',
            zurueckziehen: 'Soll dieser Trittstein nicht mehr auf der Karte erscheinen? Er zählt weiter für seine Wabe.',
            loeschen: 'Soll dieser Trittstein endgültig gelöscht werden? Er zählt danach nicht mehr.',
        })[props.action],
);

const result = computed(
    () =>
        ({
            freigeben: 'Der Trittstein ist freigegeben.',
            zurueckziehen: 'Der Trittstein ist zurückgezogen. Er zählt weiter, ist aber nicht mehr auf der Karte zu sehen.',
            loeschen: 'Der Trittstein ist gelöscht.',
        })[props.action],
);

function confirm() {
    if (props.confirmUrl) form.post(props.confirmUrl);
}
</script>

<template>
    <Head :title="`${actionLabel}: ${point.title}`" />
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-160">
            <CardContent class="space-y-4">
                <div class="text-sm text-muted-foreground">{{ point.campaign }} · angemeldet am {{ point.created_at }}</div>
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm" data-test="hexnet-moderation-point">
                    <dt class="text-muted-foreground">Trittstein</dt>
                    <dd class="font-semibold">{{ point.title }}</dd>
                    <template v-if="point.category && point.category !== point.title">
                        <dt class="text-muted-foreground">Kategorie</dt>
                        <dd>{{ point.category }}</dd>
                    </template>
                    <dt class="text-muted-foreground">Maßnahmen</dt>
                    <dd>{{ point.measures.length ? point.measures.join(', ') : 'keine' }}</dd>
                    <dt class="text-muted-foreground">Einwilligung</dt>
                    <dd>{{ point.consent }}</dd>
                    <template v-if="action !== 'loeschen' || !done">
                        <dt class="text-muted-foreground">Auf der Karte</dt>
                        <dd>{{ point.published ? 'sichtbar' : 'nicht sichtbar' }}</dd>
                    </template>
                </dl>

                <div v-if="done" class="flex items-center gap-2 font-semibold" role="status">
                    <CircleCheck class="h-5 w-5 shrink-0" style="color: #00a651" />
                    {{ result }}
                </div>
                <template v-else>
                    <p class="font-semibold">{{ question }}</p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            :variant="action === 'loeschen' ? 'destructive' : 'default'"
                            :disabled="form.processing"
                            data-test="hexnet-moderation-confirm"
                            @click="confirm"
                        >
                            {{ actionLabel }}
                        </Button>
                        <Button variant="outline" as="a" :href="point.backend_url">Im Backend ansehen</Button>
                    </div>
                </template>
                <a v-if="done && action !== 'loeschen'" :href="point.backend_url" class="block text-sm underline">Im Backend ansehen</a>
            </CardContent>
        </Card>
    </div>
</template>
