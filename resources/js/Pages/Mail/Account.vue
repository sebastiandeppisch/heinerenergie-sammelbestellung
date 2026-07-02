<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import Input from '@/shadcn/components/ui/input/Input.vue';
import Label from '@/shadcn/components/ui/label/Label.vue';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle, Loader2, Mail, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    hasEncKey: boolean;
    hasAccount: boolean;
    discoveredConfig: App.Data.MailConfigData | null;
    discoverFailed: boolean;
}>();

const hasAccount = ref(props.hasAccount);

const discoverForm = useForm({ email: '' });

const form = useForm({
    imap_host: '',
    imap_port: 993,
    smtp_host: '',
    smtp_port: 587,
    username: '',
    password: '',
    connection: null
});

watch(
    () => props.discoveredConfig,
    (config) => {
        if (!config) return;
        form.imap_host = config.imapHost;
        form.imap_port = config.imapPort;
        form.smtp_host = config.smtpHost;
        form.smtp_port = config.smtpPort;
        form.username = config.username;
    },
    { immediate: true },
);

function discover() {
    discoverForm.post(route('mail.discover'), { preserveScroll: true });
}

function save() {
    form.post(route('mail.account.store'), {
        onSuccess: () => {
            hasAccount.value = true;
        },
    });
}

const deleteForm = useForm({});

function removeAccount() {
    if (!confirm('Mail-Konto entfernen?')) return;

    deleteForm.delete(route('mail.account.destroy'), {
        onSuccess: () => {
            hasAccount.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <div class="mx-auto max-w-2xl px-6 py-12">
        <div class="mb-8 flex items-center gap-3">
            <Mail class="h-6 w-6 text-primary" />
            <h1 class="text-2xl font-semibold text-foreground">Persönliches Mail-Konto</h1>
        </div>

        <!-- Re-login notice -->
        <div v-if="!hasEncKey" class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
            <div>
                <p class="font-medium">Sitzungsschlüssel nicht verfügbar</p>
                <p class="mt-0.5 text-amber-800">Bitte melde dich erneut an, um dein Mail-Konto zu verwalten.</p>
            </div>
        </div>

        <!-- Account active banner -->
        <div v-if="hasAccount" class="mb-6 flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 p-4">
            <div class="flex items-center gap-2 text-primary">
                <CheckCircle class="h-5 w-5" />
                <span class="text-sm font-medium">Mail-Konto ist eingerichtet</span>
            </div>
            <Button
                variant="outline"
                size="sm"
                class="text-destructive hover:text-destructive"
                :disabled="deleteForm.processing"
                @click="removeAccount"
            >
                <Loader2 v-if="deleteForm.processing" class="mr-1 h-4 w-4 animate-spin" />
                <Trash2 v-else class="mr-1 h-4 w-4" />
                Entfernen
            </Button>
        </div>

        <!-- Autodiscovery -->
        <Card class="mb-6" :class="{ 'pointer-events-none opacity-50': !hasEncKey }">
            <CardHeader>
                <CardTitle>Autodiscovery</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <p class="text-sm text-muted-foreground">E-Mail-Adresse eingeben, um die Servereinstellungen automatisch zu ermitteln.</p>
                <div class="flex gap-2">
                    <Input v-model="discoverForm.email" type="email" placeholder="deine@email.de" class="flex-1" @keydown.enter.prevent="discover" />
                    <Button variant="outline" :disabled="discoverForm.processing || !discoverForm.email" @click="discover">
                        <Loader2 v-if="discoverForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Search v-else class="mr-2 h-4 w-4" />
                        Suchen
                    </Button>
                </div>
                <p v-if="discoverFailed" class="text-sm text-muted-foreground">Keine automatische Konfiguration gefunden. Bitte manuell ausfüllen.</p>
            </CardContent>
        </Card>

        <!-- Server settings -->
        <Card :class="{ 'pointer-events-none opacity-50': !hasEncKey }">
            <CardHeader>
                <CardTitle>Servereinstellungen</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="form.errors.connection" class="rounded-md border border-destructive/20 bg-destructive/5 p-3 text-sm text-destructive">
                    {{ form.errors.connection }}
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2 space-y-1">
                        <Label>IMAP-Server</Label>
                        <Input v-model="form.imap_host" placeholder="imap.example.com" />
                        <p v-if="form.errors.imap_host" class="text-sm text-destructive">{{ form.errors.imap_host }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Port</Label>
                        <Input v-model.number="form.imap_port" type="number" placeholder="993" />
                        <p v-if="form.errors.imap_port" class="text-sm text-destructive">{{ form.errors.imap_port }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2 space-y-1">
                        <Label>SMTP-Server</Label>
                        <Input v-model="form.smtp_host" placeholder="smtp.example.com" />
                        <p v-if="form.errors.smtp_host" class="text-sm text-destructive">{{ form.errors.smtp_host }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label>Port</Label>
                        <Input v-model.number="form.smtp_port" type="number" placeholder="587" />
                        <p v-if="form.errors.smtp_port" class="text-sm text-destructive">{{ form.errors.smtp_port }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label>Benutzername</Label>
                    <Input v-model="form.username" placeholder="deine@email.de" />
                    <p v-if="form.errors.username" class="text-sm text-destructive">{{ form.errors.username }}</p>
                </div>

                <div class="space-y-1">
                    <Label>Passwort</Label>
                    <Input v-model="form.password" type="password" placeholder="••••••••" />
                    <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                </div>
            </CardContent>
            <CardFooter class="justify-between">
                <p class="text-xs text-muted-foreground">Zugangsdaten werden verschlüsselt in deiner Sitzung gespeichert.</p>
                <Button :disabled="form.processing" @click="save">
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ form.processing ? 'Verbindung wird getestet…' : 'Speichern' }}
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>
