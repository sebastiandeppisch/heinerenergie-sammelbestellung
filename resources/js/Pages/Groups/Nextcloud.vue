<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, UserCheck, UserPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type NcUser = App.Data.NextcloudGroupUserData;

const props = defineProps<{
    group: App.Data.GroupData;
    items: NcUser[];
    nextcloudConfigured: boolean;
    error?: string;
}>();

const importDialogOpen = ref(false);
const importing = ref(false);
const importError = ref<string | null>(null);
const importTarget = ref<NcUser | null>(null);
const firstName = ref('');
const lastName = ref('');
const sendEmail = ref(false);
const addingToGroup = ref<string | null>(null);

function openImportDialog(item: NcUser) {
    importTarget.value = item;
    const parts = (item.nc_displayname ?? '').trim().split(' ');
    firstName.value = parts[0] ?? '';
    lastName.value = parts.slice(1).join(' ');
    importError.value = null;
    sendEmail.value = false;
    importDialogOpen.value = true;
}

function submitImport() {
    if (!importTarget.value || !importTarget.value.nc_id) return;
    importing.value = true;
    importError.value = null;

    router.post(
        route('groups.nextcloud.import', { group: props.group.id, ncUser: importTarget.value.nc_id }),
        { first_name: firstName.value, last_name: lastName.value, send_email: sendEmail.value },
        {
            onError: (errors) => {
                importError.value = errors.first_name ?? errors.last_name ?? errors.email ?? 'Fehler beim Importieren.';
                importing.value = false;
            },
            onSuccess: () => {
                importDialogOpen.value = false;
                importing.value = false;
            },
        },
    );
}

function addToGroup(item: NcUser) {
    if (!item.nc_id) return;
    addingToGroup.value = item.nc_id;
    router.post(
        route('groups.nextcloud.add-to-group', { group: props.group.id, ncUser: item.nc_id }),
        {},
        {
            onFinish: () => {
                addingToGroup.value = null;
            },
        },
    );
}

const unmatchedCount = computed(() => props.items.filter((i) => !i.crm_user || !i.crm_is_group_member).length);
</script>

<template>
    <div class="py-12">
        <div class="mx-auto space-y-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" :as-child="true">
                    <a :href="route('groups.show', group.id)">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Zurück zur Initiative
                    </a>
                </Button>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="space-y-4 p-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Nextcloud-Abgleich — {{ group.name }}</h2>
                        <p v-if="group.nextcloud_group_name" class="mt-1 text-sm text-gray-500">
                            Nextcloud-Gruppe: <span class="font-mono">{{ group.nextcloud_group_name }}</span>
                        </p>
                        <p class="mt-1 text-sm text-gray-500">Berater*innen werden über die E-Mail Adresse und ihre Gruppen-Zuordnung abgeglichen</p>
                    </div>

                    <!-- Not configured -->
                    <div v-if="!nextcloudConfigured" class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Kein Nextcloud-Gruppenname konfiguriert. Bitte trage den Nextcloud-Gruppenname in den
                        <a :href="route('groups.show', group.id)" class="font-medium underline">Stammdaten der Initiative</a>
                        ein.
                    </div>

                    <!-- API error -->
                    <div v-else-if="error" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        {{ error }}
                    </div>

                    <!-- User table -->
                    <template v-else>
                        <p v-if="unmatchedCount > 0" class="text-sm text-amber-700">
                            {{ unmatchedCount }} {{ unmatchedCount === 1 ? 'Benutzer ist' : 'Benutzer sind' }} noch nicht vollständig im CRM/Gruppe
                            vorhanden.
                        </p>
                        <p v-else-if="items.length > 0" class="text-sm text-green-700">
                            Alle {{ items.length }} Nextcloud-Benutzer sind im CRM und in dieser Gruppe vorhanden.
                        </p>
                        <p v-else class="text-sm text-gray-500">Keine Benutzer in der Nextcloud-Gruppe gefunden.</p>

                        <Table v-if="items.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Anzeigename (Nextcloud)</TableHead>
                                    <TableHead>E-Mail</TableHead>
                                    <TableHead>NC-Status</TableHead>
                                    <TableHead>CRM-Status</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in items" :key="item.nc_id ?? item.crm_user?.email">
                                    <TableCell class="font-medium">
                                        {{ item.nc_displayname || item.nc_id || item.crm_user?.name }}
                                    </TableCell>
                                    <TableCell class="text-gray-600">{{ item.nc_email ?? item.crm_user?.email }}</TableCell>
                                    <TableCell>
                                        <Badge v-if="item.nc_id !== null" class="border-green-200 bg-green-100 text-green-800"> In Nextcloud </Badge>
                                        <Badge v-else class="border-yellow-200 bg-yellow-100 text-yellow-800"> Nicht in Nextcloud </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="item.crm_is_group_member" class="border-green-200 bg-green-100 text-green-800"> Im CRM </Badge>
                                        <Badge v-else-if="item.crm_user" class="border-yellow-200 bg-yellow-100 text-yellow-800">
                                            Im CRM, nicht in Gruppe
                                        </Badge>
                                        <Badge v-else variant="outline" class="border-amber-300 text-amber-700"> Nicht im CRM </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <!-- Not in CRM at all → Import (only if NC user exists) -->
                                        <Button
                                            v-if="!item.crm_user && item.nc_id !== null"
                                            variant="outline"
                                            size="sm"
                                            @click="openImportDialog(item)"
                                        >
                                            <UserPlus class="mr-1 h-4 w-4" />
                                            Importieren
                                        </Button>
                                        <!-- In CRM but not in this group → Add to group -->
                                        <Button
                                            v-else-if="!item.crm_is_group_member"
                                            variant="outline"
                                            size="sm"
                                            :disabled="addingToGroup === item.nc_id"
                                            data-testid="add-to-group"
                                            @click="addToGroup(item)"
                                        >
                                            <UserCheck class="mr-1 h-4 w-4" />
                                            Zur Gruppe hinzufügen
                                        </Button>
                                        <!-- Already in group -->
                                        <span v-else class="text-sm text-gray-500">{{ item.crm_user?.name }}</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Import dialog -->
    <Dialog v-model:open="importDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Benutzer ins CRM importieren</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <div class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600">
                    <span class="font-medium">E-Mail:</span> {{ importTarget?.nc_email }}
                </div>

                <div class="space-y-1.5">
                    <Label for="import-first-name">Vorname</Label>
                    <Input id="import-first-name" v-model="firstName" />
                </div>

                <div class="space-y-1.5">
                    <Label for="import-last-name">Nachname</Label>
                    <Input id="import-last-name" v-model="lastName" @keydown.enter="submitImport" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox id="import-send-email" v-model="sendEmail" />
                    <Label for="import-send-email" class="cursor-pointer font-normal"> E-Mail zum Passwort-Setzen senden </Label>
                </div>

                <p v-if="importError" class="text-sm text-red-600">{{ importError }}</p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="importDialogOpen = false">Abbrechen</Button>
                <Button data-testid="import-confirm" :disabled="importing || !firstName.trim() || !lastName.trim()" @click="submitImport">
                    {{ importing ? 'Wird importiert…' : 'Importieren' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
