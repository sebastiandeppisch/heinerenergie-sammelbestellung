<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router, setLayoutProps } from '@inertiajs/vue3';
import { AlertTriangle, Edit, ExternalLink, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

defineProps<{
    mapEmbeds: Array<App.Data.MapEmbedData>;
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte', href: route('mappoints.index') }, { title: 'Einbettungen' }],
});

const showDeleteDialog = ref(false);
const mapEmbedIdToDelete = ref<string | null>(null);

function confirmDelete(mapEmbedId: string) {
    mapEmbedIdToDelete.value = mapEmbedId;
    showDeleteDialog.value = true;
}

function deleteMapEmbed() {
    if (mapEmbedIdToDelete.value) {
        router.delete(route('map-embeds.destroy', mapEmbedIdToDelete.value), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                mapEmbedIdToDelete.value = null;
            },
        });
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl">
        <PageHeader title="Karten-Einbettungen" description="Verwalte Links, mit denen die Karte auf anderen Webseiten eingebettet werden kann">
            <template #actions>
                <Button @click="router.visit(route('map-embeds.create'))">
                    <Plus class="mr-2 h-4 w-4" />
                    Neue Einbettung
                </Button>
            </template>
        </PageHeader>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Kategorien</TableHead>
                            <TableHead>Erstellt</TableHead>
                            <TableHead class="text-right">Aktionen</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="mapEmbed in mapEmbeds" :key="mapEmbed.id">
                            <TableCell class="font-medium">
                                {{ mapEmbed.name || '-' }}
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="category in mapEmbed.categories" :key="category.id" variant="secondary">
                                        {{ category.name }}
                                    </Badge>
                                    <Badge v-if="mapEmbed.categories.length === 0" variant="destructive">
                                        <AlertTriangle class="mr-1 h-3 w-3" />
                                        Keine Kategorien – Karte ist leer
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-gray-500">
                                {{ mapEmbed.created_at ? new Date(mapEmbed.created_at).toLocaleDateString('de-DE') : '-' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        as="a"
                                        :href="route('map.public', mapEmbed.id)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        variant="outline"
                                        size="sm"
                                        title="Link öffnen"
                                        aria-label="Link öffnen"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </Button>
                                    <Button variant="outline" size="sm" @click="router.visit(route('map-embeds.edit', mapEmbed.id))">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="confirmDelete(mapEmbed.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="mapEmbeds.length === 0">
                            <TableCell colspan="4" class="py-8 text-center text-gray-500"> Noch keine Einbettungen erstellt </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Einbettung wirklich löschen?</DialogTitle>
                <DialogDescription>Der zugehörige Einbettungslink funktioniert danach nicht mehr.</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteDialog = false">Abbrechen</Button>
                <Button variant="destructive" @click="deleteMapEmbed">Löschen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
