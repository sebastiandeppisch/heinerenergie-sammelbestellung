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
                        <div class="flex items-center gap-3 mb-4">
                            <Database class="h-6 w-6 text-primary" />
                            <h3 class="text-xl font-semibold">Datenbank-Migrationen</h3>
                        </div>
                        <p class="text-muted-foreground mb-4">
                            Führt alle ausstehenden Datenbank-Migrationen aus. Dies aktualisiert die Datenbankstruktur
                            auf den neuesten Stand.
                        </p>
                        <Button @click="executeMigrate" variant="default" class="w-full sm:w-auto">
                            <Terminal class="mr-2 h-4 w-4" />
                            Migration ausführen (migrate --force)
                        </Button>
                        <div v-if="migrateOutput" class="mt-4 p-4 bg-slate-100 dark:bg-slate-800 rounded-md">
                            <pre class="text-sm whitespace-pre-wrap font-mono">{{ migrateOutput }}</pre>
                        </div>
                    </div>
                </Card>

                <!-- Seed Card -->
                <Card>
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <Database class="h-6 w-6 text-primary" />
                            <h3 class="text-xl font-semibold">Datenbank-Seeding</h3>
                        </div>
                        <p class="text-muted-foreground mb-4">
                            Füllt die Datenbank mit Standard-Daten. Dies kann bestehende Daten überschreiben.
                        </p>
                        <Button @click="executeSeed" variant="default" class="w-full sm:w-auto">
                            <Terminal class="mr-2 h-4 w-4" />
                            Seeding ausführen (db:seed --force)
                        </Button>
                        <div v-if="seedOutput" class="mt-4 p-4 bg-slate-100 dark:bg-slate-800 rounded-md">
                            <pre class="text-sm whitespace-pre-wrap font-mono">{{ seedOutput }}</pre>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>

