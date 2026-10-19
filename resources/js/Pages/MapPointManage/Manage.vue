<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';
import type { CustomPageProps } from '@/types/pageProps';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    token: string;
    mapPoint: App.Data.MapPointManageData;
}>();

const page = usePage<CustomPageProps>();
const success = computed(() => page.props.flashMessages?.success ?? null);

const form = useForm({
    show: props.mapPoint.public_consent === 'given',
    details: props.mapPoint.shows_details,
});

const confirmingDelete = ref(false);

function updateShow(checked: boolean | 'indeterminate') {
    form.show = checked === true;

    if (!form.show) {
        form.details = false;
    }
}

function save() {
    form.put(route('map-point.manage.consent', props.token), { preserveScroll: true });
}

function revoke() {
    router.post(route('map-point.manage.revoke', props.token), {}, { preserveScroll: true });
}

function destroy() {
    router.delete(route('map-point.manage.destroy', props.token));
}
</script>

<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent class="flex flex-col gap-6">
                <div class="flex flex-col gap-1">
                    <h1 class="text-xl font-semibold">{{ mapPoint.title }}</h1>
                    <p v-if="mapPoint.category" class="text-sm text-muted-foreground">
                        {{ mapPoint.category
                        }}<template v-if="mapPoint.characteristics.length > 0"> · {{ mapPoint.characteristics.join(', ') }}</template>
                    </p>
                    <p v-if="mapPoint.description" class="mt-2 text-sm whitespace-pre-line">{{ mapPoint.description }}</p>
                </div>

                <p v-if="success" class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" data-test="manage-success">
                    {{ success }}
                </p>

                <section class="flex flex-col gap-2">
                    <h2 class="font-semibold">Was ist öffentlich?</h2>
                    <div class="flex items-center gap-2">
                        <Badge :variant="mapPoint.public_consent === 'refused' ? 'destructive' : 'default'" data-test="manage-consent-status">
                            Einwilligung: {{ mapPoint.public_consent_label }}
                        </Badge>
                        <span v-if="!mapPoint.published && mapPoint.public_consent !== 'refused'" class="text-sm text-muted-foreground">
                            Noch nicht freigegeben, der Eintrag erscheint erst nach unserer Prüfung.
                        </span>
                    </div>
                    <p v-if="mapPoint.public_consent === 'given'" class="text-sm">
                        Gezeigt werden: {{ mapPoint.consented_labels.join(', ') }}. Ohne Titel erscheint der Name der Kategorie.
                    </p>
                    <p v-else-if="mapPoint.public_consent === 'refused'" class="text-sm">Dein Eintrag wird nicht öffentlich angezeigt.</p>
                </section>

                <form class="flex flex-col gap-3 rounded-md border p-4" @submit.prevent="save">
                    <h2 class="font-semibold">Auswahl ändern</h2>
                    <div class="flex items-start gap-2">
                        <Checkbox id="manage-show" :model-value="form.show" @update:model-value="updateShow" />
                        <Label for="manage-show" class="text-sm font-normal">Meinen Eintrag auf der Karte zeigen</Label>
                    </div>
                    <div v-if="form.show" class="ml-6 flex items-start gap-2">
                        <Checkbox
                            id="manage-details"
                            :model-value="form.details"
                            @update:model-value="(checked) => (form.details = checked === true)"
                        />
                        <Label for="manage-details" class="text-sm font-normal"
                            >zusätzlich diese Angaben zeigen: {{ mapPoint.offered_labels.join(', ') }}</Label
                        >
                    </div>
                    <p v-if="form.show" class="text-sm text-muted-foreground">{{ mapPoint.consent_text }}</p>
                    <div>
                        <Button type="submit" :disabled="form.processing">Auswahl speichern</Button>
                    </div>
                </form>

                <section class="flex flex-col gap-3 rounded-md border p-4">
                    <h2 class="font-semibold">Widerrufen oder löschen</h2>
                    <p class="text-sm text-muted-foreground">
                        Nach dem Widerruf erscheint dein Eintrag nicht mehr auf der Karte, bleibt aber bei uns gespeichert. Beim Löschen entfernen wir
                        den Eintrag, seine Bilder und deine Angaben aus dem Formular endgültig.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button v-if="mapPoint.public_consent !== 'refused'" variant="outline" @click="revoke">Einwilligung widerrufen</Button>
                        <Button v-if="!confirmingDelete" variant="destructive" @click="confirmingDelete = true">Eintrag löschen</Button>
                        <template v-else>
                            <Button variant="destructive" data-test="manage-delete-confirm" @click="destroy">Ja, endgültig löschen</Button>
                            <Button variant="outline" @click="confirmingDelete = false">Abbrechen</Button>
                        </template>
                    </div>
                </section>
            </CardContent>
        </Card>
    </div>
</template>
