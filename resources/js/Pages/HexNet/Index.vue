<script setup lang="ts">
import HexNetLogo from '@/components/HexNet/HexNetLogo.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router, setLayoutProps } from '@inertiajs/vue3';
import { Edit, ExternalLink, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    hexNets: Array<App.HexNet.Data.HexNetData>;
}>();

setLayoutProps({ breadcrumbs: [{ title: 'Kampagnen' }] });

const running = computed(() => props.hexNets.filter((hexNet) => hexNet.status === 'active'));
const drafts = computed(() => props.hexNets.filter((hexNet) => hexNet.status === 'draft'));
const sections = computed(() => [
    { title: 'Laufende Kampagnen', empty: 'Noch keine laufende Kampagne', items: running.value },
    { title: 'Entwürfe', empty: 'Keine Entwürfe', items: drafts.value },
]);

const toDelete = ref<App.HexNet.Data.HexNetData | null>(null);

function destroy() {
    if (!toDelete.value) return;
    router.delete(route('hexnets.destroy', toDelete.value.id), { onFinish: () => (toDelete.value = null) });
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl space-y-8">
        <PageHeader title="Kampagnen" description="Starte eine Mitmach-Kampagne auf Basis eurer Kartenpunkte und verfolge den Spielstand" />

        <section>
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">Neue Kampagne starten</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card class="flex flex-col" data-test="hexnet-template">
                    <CardHeader>
                        <HexNetLogo class="mb-2 h-24 w-auto self-center" />
                        <CardTitle>HexaNetz</CardTitle>
                        <CardDescription>
                            Ein Sechseck-Raster über eurem Gebiet: Jeder gemeldete Trittstein färbt seine Wabe grün. Ziel ist ein lückenloses Netz für
                            Insekten.
                        </CardDescription>
                    </CardHeader>
                    <CardFooter class="mt-auto">
                        <Button class="w-full" @click="router.visit(route('hexnets.create'))">
                            <Plus class="mr-2 h-4 w-4" />
                            HexaNetz starten
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </section>

        <section v-for="section in sections" :key="section.title">
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">{{ section.title }}</h2>
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Art</TableHead>
                                <TableHead>Initiative</TableHead>
                                <TableHead class="text-right">Spielstand</TableHead>
                                <TableHead class="text-right">Trittsteine</TableHead>
                                <TableHead class="text-right">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="hexNet in section.items" :key="hexNet.id">
                                <TableCell class="font-medium">
                                    {{ hexNet.name }}
                                    <div class="text-xs text-muted-foreground">/hexanetz/{{ hexNet.slug }}</div>
                                </TableCell>
                                <TableCell><Badge variant="secondary">HexaNetz</Badge></TableCell>
                                <TableCell>{{ hexNet.group_name }}</TableCell>
                                <TableCell class="text-right font-mono">{{ hexNet.stats?.percent ?? 0 }} %</TableCell>
                                <TableCell class="text-right font-mono">{{ hexNet.stats?.total_points ?? 0 }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            as="a"
                                            :href="hexNet.public_url"
                                            target="_blank"
                                            rel="noopener"
                                            variant="outline"
                                            size="sm"
                                            title="Öffentliche Seite"
                                        >
                                            <ExternalLink class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            title="Bearbeiten"
                                            @click="router.visit(route('hexnets.edit', hexNet.id))"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="destructive" size="sm" title="Löschen" @click="toDelete = hexNet">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="section.items.length === 0">
                                <TableCell colspan="6" class="py-8 text-center text-muted-foreground">{{ section.empty }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </section>
    </div>

    <Dialog :open="toDelete !== null" @update:open="(open) => !open && (toDelete = null)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>HexaNetz „{{ toDelete?.name }}“ löschen?</DialogTitle>
                <DialogDescription>Die Kartenpunkte, Kategorien und das Formular bleiben erhalten. Nur die Kampagne verschwindet.</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="toDelete = null">Abbrechen</Button>
                <Button variant="destructive" @click="destroy">Löschen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
