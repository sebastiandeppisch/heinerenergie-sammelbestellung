<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPointId: string;
    consent: App.Data.MapPointConsentInfoData;
}>();

const confirmingRefuse = ref(false);

function refuse() {
    router.post(route('mappoints.refuse-consent', props.mapPointId), {}, { preserveScroll: true, onFinish: () => (confirmingRefuse.value = false) });
}

function sendEditLink() {
    router.post(route('mappoints.send-edit-link', props.mapPointId), {}, { preserveScroll: true });
}

function formatDate(date: string): string {
    return new Date(date).toLocaleString('de-DE', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Card data-test="map-point-consent-card">
        <CardContent class="space-y-4">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold">Einwilligung zur Veröffentlichung</h2>
                <Badge :variant="consent.public_consent === 'refused' ? 'destructive' : 'secondary'" data-test="map-point-consent-status">
                    {{ consent.public_consent_label }}
                </Badge>
            </div>

            <p v-if="consent.public_consent === 'not_required'" class="text-sm text-gray-500">
                Der Punkt wurde nicht über ein Formular mit Einwilligung angelegt. Öffentlich ist er, sobald er veröffentlicht ist.
            </p>
            <p v-else-if="consent.public_consent === 'refused'" class="text-sm text-gray-500">
                Der Punkt erscheint nie öffentlich, auch wenn er veröffentlicht ist. Nur die Person selbst kann wieder einwilligen.
            </p>

            <dl class="grid gap-2 text-sm sm:grid-cols-[max-content_1fr] sm:gap-x-4">
                <template v-if="consent.public_consent_at">
                    <dt class="text-gray-500">Zeitpunkt</dt>
                    <dd>{{ formatDate(consent.public_consent_at) }}</dd>
                </template>
                <template v-if="consent.public_consent === 'given'">
                    <dt class="text-gray-500">Eingewilligt in</dt>
                    <dd>{{ consent.field_labels.join(', ') }}</dd>
                </template>
                <template v-if="consent.public_consent_text">
                    <dt class="text-gray-500">Text</dt>
                    <dd class="whitespace-pre-line">{{ consent.public_consent_text }}</dd>
                </template>
                <template v-if="consent.submission_id">
                    <dt class="text-gray-500">Einsendung</dt>
                    <dd>
                        <a :href="route('form-submissions.index', { submission: consent.submission_id })" class="underline">
                            Einsendung mit allen Angaben öffnen
                        </a>
                    </dd>
                </template>
            </dl>

            <div class="flex flex-wrap gap-2">
                <Button v-if="consent.contact_email" variant="outline" @click="sendEditLink">Verwaltungslink an Kontaktadresse senden</Button>
                <template v-if="consent.public_consent !== 'refused'">
                    <Button v-if="!confirmingRefuse" variant="destructive" @click="confirmingRefuse = true"
                        >Einwilligung als widerrufen markieren</Button
                    >
                    <template v-else>
                        <Button variant="destructive" data-test="map-point-refuse-confirm" @click="refuse">Ja, nicht mehr öffentlich zeigen</Button>
                        <Button variant="outline" @click="confirmingRefuse = false">Abbrechen</Button>
                    </template>
                </template>
            </div>
        </CardContent>
    </Card>
</template>
