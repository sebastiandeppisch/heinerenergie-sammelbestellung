<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import { router } from '@inertiajs/vue3';
import { Database, Terminal } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

interface CommandResult {
    output: string;
}

const props = defineProps<{
    migrateResult?: CommandResult;
    seedResult?: CommandResult;
}>();

const migrateOutput = computed(() => props.migrateResult?.output || null);
const seedOutput = computed(() => props.seedResult?.output || null);

function executeMigrate() {
    router.post(route('system-admin.migrate'));
}

function executeSeed() {
    router.post(route('system-admin.seed'));
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
            </div>
        </div>
    </div>
</template>
