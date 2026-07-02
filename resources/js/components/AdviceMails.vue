<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import Input from '@/shadcn/components/ui/input/Input.vue';
import Label from '@/shadcn/components/ui/label/Label.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Loader2, Mail, MailOpen, Plus, Send, Settings, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

type MailHeader = App.Data.MailHeaderData;

type MailBody = App.Data.MailBodyData;

const props = defineProps<{
    adviceId: string;
    contactEmail: string;
}>();

const loading = ref(false);
const mails = ref<MailHeader[]>([]);
const selectedMail = ref<MailBody | null>(null);
const loadingBody = ref(false);
const error = ref<string | null>(null);
const showCompose = ref(false);

const compose = ref({
    subject: '',
    body: '',
    sending: false,
});

async function loadMails() {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get(route('api.mail.index', { advice: props.adviceId }));
        mails.value = data;
    } catch (e: any) {
        error.value = e?.response?.status === 403 ? 'no_key' : 'Fehler beim Laden der E-Mails.';
    } finally {
        loading.value = false;
    }
}

async function openMail(uid: string, folder: string) {
    loadingBody.value = true;
    selectedMail.value = null;

    try {
        const { data } = await axios.get(route('api.mail.show', { advice: props.adviceId, folder, uid }));
        selectedMail.value = data;
    } finally {
        loadingBody.value = false;
    }
}

async function sendMail() {
    if (!compose.value.subject || !compose.value.body) return;
    compose.value.sending = true;

    try {
        await axios.post(route('api.mail.store', { advice: props.adviceId }), {
            subject: compose.value.subject,
            body: compose.value.body,
        });

        compose.value.subject = '';
        compose.value.body = '';
        showCompose.value = false;
        await loadMails();
    } finally {
        compose.value.sending = false;
    }
}

onMounted(loadMails);
</script>

<template>
    <div class="p-4">
        <!-- No mail account / no encryption key -->
        <div v-if="error === 'no_key'" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-medium">Mail-Konto nicht verfügbar</p>
            <p class="mt-1">
                Richte dein
                <Link :href="route('mail.account.show')" class="underline">persönliches Mail-Konto</Link>
                ein oder melde dich erneut an.
            </p>
        </div>

        <!-- Generic error -->
        <div v-else-if="error" class="rounded-lg border border-destructive/20 bg-destructive/5 p-4 text-sm text-destructive">
            {{ error }}
            <p class="mt-1">
                Richte ggf. zuerst dein
                <Link :href="route('mail.account.show')" class="underline">persönliches Mail-Konto</Link>
                ein oder melde dich erneut an.
            </p>
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="flex items-center gap-2 py-6 text-sm text-muted-foreground">
            <Loader2 class="h-4 w-4 animate-spin" />
            E-Mails werden geladen…
        </div>

        <template v-else>
            <!-- Toolbar -->
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm text-muted-foreground">{{ mails.length }} E-Mail{{ mails.length !== 1 ? 's' : '' }}</span>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('mail.account.show')">
                            <Settings class="h-3 w-3" />
                        </Link>
                    </Button>
                    <Button size="sm" @click="showCompose = !showCompose">
                        <Plus class="mr-1 h-4 w-4" />
                        Neue E-Mail
                    </Button>
                </div>
            </div>

            <!-- Compose form -->
            <Card v-if="showCompose" class="mb-4">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Neue E-Mail an {{ contactEmail }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-1">
                        <Label>Betreff</Label>
                        <Input v-model="compose.subject" placeholder="Betreff" />
                    </div>
                    <div class="space-y-1">
                        <Label>Nachricht</Label>
                        <textarea
                            v-model="compose.body"
                            rows="5"
                            placeholder="Deine Nachricht..."
                            class="w-full rounded-md border border-input bg-background p-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showCompose = false">Abbrechen</Button>
                        <Button size="sm" :disabled="compose.sending || !compose.subject || !compose.body" @click="sendMail">
                            <Loader2 v-if="compose.sending" class="mr-2 h-4 w-4 animate-spin" />
                            <Send v-else class="mr-2 h-4 w-4" />
                            Senden
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Mail list -->
            <div v-if="mails.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                <Mail class="mx-auto mb-2 h-8 w-8 text-muted-foreground/40" />
                Keine E-Mails mit {{ contactEmail }} gefunden.
            </div>

            <div v-else class="divide-y divide-border">
                <button
                    v-for="mail in mails"
                    :key="`${mail.folder}:${mail.uid}`"
                    class="w-full px-3 py-3 text-left transition-colors hover:bg-muted/50"
                    :class="{ 'bg-primary/5': selectedMail?.uid === mail.uid && selectedMail?.folder === mail.folder }"
                    @click="openMail(mail.uid, mail.folder)"
                >
                    <div class="flex items-start gap-2">
                        <MailOpen v-if="mail.hasBeenRead" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <Mail v-else class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm text-foreground" :class="mail.hasBeenRead ? 'font-normal' : 'font-medium'">
                                    {{ mail.subject || '(kein Betreff)' }}
                                </span>
                                <span class="shrink-0 text-xs text-muted-foreground">{{ mail.date }}</span>
                            </div>
                            <p class="truncate text-xs text-muted-foreground">{{ mail.from }}</p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Mail body -->
            <div v-if="loadingBody" class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                <Loader2 class="h-4 w-4 animate-spin" />
                Lädt…
            </div>

            <Card v-else-if="selectedMail" class="mt-4">
                <CardHeader class="pb-2">
                    <div class="flex items-start justify-between gap-2">
                        <CardTitle class="text-sm">{{ selectedMail.subject || '(kein Betreff)' }}</CardTitle>
                        <Button variant="ghost" size="icon" class="h-6 w-6 shrink-0 text-muted-foreground" @click="selectedMail = null">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                    <div class="space-y-0.5 text-xs text-muted-foreground">
                        <p>Von: {{ selectedMail.from }}</p>
                        <p>An: {{ selectedMail.to }}</p>
                        <p>{{ selectedMail.date }}</p>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm whitespace-pre-wrap text-foreground">{{ selectedMail.body }}</p>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
