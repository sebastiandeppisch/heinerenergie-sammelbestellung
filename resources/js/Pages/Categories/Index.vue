<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent } from '@/shadcn/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { flattenCategoryTree } from '@/utils/categoryTree';
import { Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

function pointCount(count: number): string {
    return count === 1 ? '1 Punkt' : `${count} Punkte`;
}

const props = defineProps<{
    categories: Array<App.Data.MapPointCategoryData>;
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte', href: route('mappoints.index') }, { title: 'Kategorien' }],
});

const entries = computed(() => flattenCategoryTree(props.categories));

function deleteCategory(category: App.Data.MapPointCategoryData) {
    const consequence = category.parent_id
        ? 'Die Unterkategorien und Kartenpunkte werden der Oberkategorie zugeordnet.'
        : 'Die Unterkategorien werden zu Hauptkategorien, Kartenpunkte aller Initiativen verlieren die Kategorie.';
    const characteristics =
        category.characteristics_count === 0
            ? ''
            : ` ${category.characteristics_count === 1 ? 'Ihre Maßnahme wird' : `Ihre ${category.characteristics_count} Maßnahmen werden`} gelöscht, die Werte bleiben an den Punkten als frühere Angaben erhalten.`;

    if (confirm(`Bist du sicher, dass du diese Kategorie löschen möchtest? ${consequence}${characteristics}`)) {
        router.delete(route('mappoint-categories.destroy', category.id));
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
                        <TableRow v-for="{ category, depth } in entries" :key="category.id">
                            <TableCell>
                                <div class="flex h-12 w-12 items-center justify-center">
                                    <img
                                        v-if="category.marker_image_path"
                                        :src="category.marker_image_path"
                                        :alt="category.name"
                                        class="h-full w-full object-contain"
                                        :class="{ 'opacity-50': !category.image_path }"
                                        :title="category.image_path ? undefined : 'Bild der Oberkategorie'"
                                    />
                                    <span v-else class="text-xs text-gray-400">Kein Bild</span>
                                </div>
                            </TableCell>
                            <!-- Long names are cut with an ellipsis, the full name shows on hover. -->
                            <TableCell class="max-w-md font-medium">
                                <div class="flex min-w-0 items-center" :style="{ paddingLeft: `${depth * 1.5}rem` }" :title="category.name">
                                    <span v-if="depth > 0" class="mr-1 shrink-0 text-gray-400">↳</span>
                                    <span class="truncate" data-test="category-name">{{ category.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-gray-600">
                                {{ category.group_name }}
                            </TableCell>
                            <TableCell>
                                <!-- Links to the points of the category and its sub categories, e.g. to delete them at once. -->
                                <Link
                                    :href="route('mappoints.index', { category: category.id })"
                                    class="inline-flex flex-col items-start gap-1"
                                    data-test="category-point-count"
                                >
                                    <Badge variant="secondary">{{ pointCount(category.map_points_count) }}</Badge>
                                    <span
                                        v-if="(category.map_points_with_subcategories_count ?? 0) > category.map_points_count"
                                        class="text-xs text-gray-500"
                                    >
                                        {{ category.map_points_with_subcategories_count }} mit Unterkategorien
                                    </span>
                                </Link>
                            </TableCell>
                            <TableCell class="text-gray-500">
                                {{ category.created_at ? new Date(category.created_at).toLocaleDateString('de-DE') : '-' }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div v-if="category.can_edit" class="flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="router.visit(route('mappoint-categories.edit', category.id))">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteCategory(category)">
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
