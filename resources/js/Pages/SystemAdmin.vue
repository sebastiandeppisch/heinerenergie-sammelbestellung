<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { router } from '@inertiajs/vue3';
import { Database, MapPin, Terminal } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface CommandResult {
    output: string;
}

interface Geocoding {
    interval: number;
    currentWait: number;
    maxWait: number;
    queueConnection: string;
    counts: Record<string, number>;
}

const props = defineProps<{
    migrateResult?: CommandResult;
    seedResult?: CommandResult;
    geocodingResult?: CommandResult;
    geocoding: Geocoding;
}>();

const migrateOutput = computed(() => props.migrateResult?.output || null);
const seedOutput = computed(() => props.seedResult?.output || null);
const geocodingOutput = computed(() => props.geocodingResult?.output || null);

const geocodingLabels: Record<string, string> = {
    success: 'Koordinaten ermittelt',
    manual: 'Position von Hand gesetzt',
    pending: 'Wird noch ermittelt',
    not_found: 'Adresse nicht gefunden',
    failed: 'Adressdienst nicht erreichbar',
    unknown: 'Ohne Status (vor der Umstellung angelegt)',
};

const geocodingRows = computed(() =>
    Object.entries(geocodingLabels).map(([key, label]) => ({
        key,
        label,
        count: props.geocoding.counts[key] ?? 0,
    })),
);

/** Only these are picked up again by the catch up. */
const retryableCount = computed(() => (props.geocoding.counts.pending ?? 0) + (props.geocoding.counts.failed ?? 0));

/** Requests are queueing up behind the rate limit right now. */
const isThrottling = computed(() => props.geocoding.currentWait > 0);

const isGeocoding = ref(false);

function executeMigrate() {
    router.post(route('system-admin.migrate'));
}

function executeSeed() {
    router.post(route('system-admin.seed'));
}

function executeGeocoding() {
    isGeocoding.value = true;
    router.post(route('system-admin.geocode-pending'), undefined, {
        onFinish: () => (isGeocoding.value = false),
    });
}
</script>

<template>
    <div>
        <h2 class="content-block">System-Administration</h2>
        <div style="margin: 30px 40px 30px 40px">
            <div class="space-y-6">
                <!-- Migration Card -->
                <Card>
                    <div class="p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <Database class="h-6 w-6 text-primary" />
                            <h3 class="text-xl font-semibold">Datenbank-Migrationen</h3>
                        </div>
                        <p class="mb-4 text-muted-foreground">
                            Führt alle ausstehenden Datenbank-Migrationen aus. Dies aktualisiert die Datenbankstruktur auf den neuesten Stand.
                        </p>
                        <Button @click="executeMigrate" variant="default" class="w-full sm:w-auto">
                            <Terminal class="mr-2 h-4 w-4" />
                            Migration ausführen (migrate --force)
                        </Button>
                        <div v-if="migrateOutput" class="mt-4 rounded-md bg-slate-100 p-4 dark:bg-slate-800">
                            <pre class="font-mono text-sm whitespace-pre-wrap">{{ migrateOutput }}</pre>
                        </div>
                    </div>
                </Card>

                <!-- Seed Card -->
                <Card>
                    <div class="p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <Database class="h-6 w-6 text-primary" />
                            <h3 class="text-xl font-semibold">Datenbank-Seeding</h3>
                        </div>
                        <p class="mb-4 text-muted-foreground">Füllt die Datenbank mit Standard-Daten. Dies kann bestehende Daten überschreiben.</p>
                        <Button @click="executeSeed" variant="default" class="w-full sm:w-auto">
                            <Terminal class="mr-2 h-4 w-4" />
                            Seeding ausführen (db:seed --force)
                        </Button>
                        <div v-if="seedOutput" class="mt-4 rounded-md bg-slate-100 p-4 dark:bg-slate-800">
                            <pre class="font-mono text-sm whitespace-pre-wrap">{{ seedOutput }}</pre>
                        </div>
                    </div>
                </Card>

                <!-- Geocoding Card -->
                <Card>
                    <div class="p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <MapPin class="h-6 w-6 text-primary" />
                            <h3 class="text-xl font-semibold">Geocoding (OpenStreetMap)</h3>
                        </div>

                        <p class="mb-4 text-muted-foreground">
                            Adressen werden über OpenStreetMap in Koordinaten übersetzt. Der Dienst erlaubt nur wenige Anfragen, deshalb wartet der
                            Server zwischen zwei Anfragen
                            <strong>{{ geocoding.interval }} Sekunden</strong>. Die Ermittlung läuft über die Queue-Verbindung
                            <strong>{{ geocoding.queueConnection }}</strong
                            >.
                        </p>

                        <div class="mb-4 rounded-md border px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Aktuelle Wartezeit bis zur nächsten Anfrage</span>
                                <span class="font-mono text-sm font-semibold" :class="{ 'text-amber-600': isThrottling }">
                                    {{ geocoding.currentWait }} s
                                </span>
                            </div>
                            <p v-if="isThrottling" class="mt-2 text-sm text-amber-600">
                                Es wird gerade gedrosselt. Ab {{ geocoding.maxWait }} Sekunden Rückstau werden weitere Anfragen abgewiesen, statt noch
                                länger zu warten.
                            </p>
                            <p v-else class="mt-2 text-sm text-muted-foreground">
                                Keine Anfrage im Rückstau, die nächste Adresse wird sofort ermittelt.
                            </p>
                        </div>

                        <dl class="mb-4 divide-y rounded-md border">
                            <div v-for="row in geocodingRows" :key="row.key" class="flex items-center justify-between px-4 py-2">
                                <dt class="text-sm">{{ row.label }}</dt>
                                <dd class="font-mono text-sm font-semibold">{{ row.count }}</dd>
                            </div>
                        </dl>

                        <p v-if="retryableCount === 0" class="mb-4 text-sm text-muted-foreground">
                            Es wartet gerade keine Beratung auf eine Positionsermittlung.
                        </p>
                        <p v-else class="mb-4 text-sm text-muted-foreground">
                            {{ retryableCount }} Beratungen können erneut versucht werden. Pro Durchlauf werden bis zu 10 davon bearbeitet, das dauert
                            wegen der Wartezeit bis zu {{ Math.round(10 * geocoding.interval) }} Sekunden.
                        </p>

                        <Button @click="executeGeocoding" :disabled="isGeocoding || retryableCount === 0" variant="default" class="w-full sm:w-auto">
                            <Terminal class="mr-2 h-4 w-4" />
                            {{ isGeocoding ? 'Läuft...' : 'Offene Positionen jetzt ermitteln' }}
                        </Button>

                        <div v-if="geocodingOutput" class="mt-4 rounded-md bg-slate-100 p-4 dark:bg-slate-800">
                            <pre class="font-mono text-sm whitespace-pre-wrap">{{ geocodingOutput }}</pre>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>
