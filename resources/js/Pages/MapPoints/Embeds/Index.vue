<script setup lang="ts">
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Edit, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

defineProps<{
    mapEmbeds: Array<App.Data.MapEmbedData>;
}>();

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
    <div class="container mx-auto py-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Karten-Einbettungen</h1>
                    <p class="mt-2 text-gray-600">Verwalte Links, mit denen die Karte auf anderen Webseiten eingebettet werden kann</p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" @click="router.visit(route('mappoints.index'))">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Zurück zu Kartenpunkten
                    </Button>

                    <Button @click="router.visit(route('map-embeds.create'))">
                        <Plus class="mr-2 h-4 w-4" />
                        Neue Einbettung
                    </Button>
                </div>
            </div>

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
