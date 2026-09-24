<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { router, setLayoutProps } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    categories: Array<App.Data.MapPointCategoryData>;
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte', href: route('mappoints.index') }, { title: 'Kategorien' }],
});

function deleteCategory(categoryId: string) {
    if (
        confirm(
            'Sind Sie sicher, dass Sie diese Kategorie löschen möchten? Kartenpunkte aller Initiativen, die diese Kategorie nutzen, verlieren sie dabei.',
        )
    ) {
        router.delete(route('mappoint-categories.destroy', categoryId));
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl">
        <PageHeader title="Kategorien" description="Verwalte Kategorien für Kartenpunkte">
            <template #actions>
                <Button @click="router.visit(route('mappoint-categories.create'))">
                    <Plus class="mr-2 h-4 w-4" />
                    Neue Kategorie
                </Button>
            </template>
        </PageHeader>

        <Card>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bild</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Initiative</TableHead>
                            <TableHead>Kartenpunkte</TableHead>
                            <TableHead>Erstellt</TableHead>
                            <TableHead class="text-right">Aktionen</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="category in categories" :key="category.id">
                            <TableCell>
                                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                    <img
                                        v-if="category.image_path"
                                        :src="category.image_path"
                                        :alt="category.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="text-xs text-gray-400">Kein Bild</span>
                                </div>
                            </TableCell>
                            <TableCell class="font-medium">
                                {{ category.name }}
                            </TableCell>
                            <TableCell class="text-gray-600">
                                {{ category.group_name }}
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary"> {{ category.map_points_count }} Punkte </Badge>
                            </TableCell>
                            <TableCell class="text-gray-500">
                                {{ category.created_at ? new Date(category.created_at).toLocaleDateString('de-DE') : '-' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div v-if="category.can_edit" class="flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="router.visit(route('mappoint-categories.edit', category.id))">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteCategory(category.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Badge
                                    v-else
                                    variant="outline"
                                    title="Diese Kategorie gehört einer übergeordneten Initiative und kann hier nur genutzt werden."
                                >
                                    Geerbt
                                </Badge>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="categories.length === 0">
                            <TableCell colspan="6" class="py-8 text-center text-gray-500"> Noch keine Kategorien erstellt </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
