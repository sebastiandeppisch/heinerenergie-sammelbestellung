<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { router } from '@inertiajs/vue3';
import { GripVertical, ListPlus, Pencil, Plus, Trash2 } from '@lucide/vue';
import { moveArrayElement, useSortable } from '@vueuse/integrations/useSortable';
import type { SortableEvent } from 'sortablejs';
import { nextTick, ref, useTemplateRef, watch } from 'vue';
import { route } from 'ziggy-js';
import MapPointCharacteristicBadge from './MapPointCharacteristicBadge.vue';
import MapPointCharacteristicDialog from './MapPointCharacteristicDialog.vue';

const props = defineProps<{
    categoryId: string;
    characteristics: Array<App.Data.MapPointCharacteristicData>;
    /** Characteristics of the parent categories, which points of this category can have as well. */
    inheritedCharacteristics: Array<App.Data.MapPointCharacteristicData>;
}>();

const sortedCharacteristics = ref([...props.characteristics]);

watch(
    () => props.characteristics,
    (characteristics) => {
        sortedCharacteristics.value = [...characteristics];
    },
);

const list = useTemplateRef<HTMLElement>('list');

useSortable(list, sortedCharacteristics, {
    animation: 150,
    handle: '.characteristic-drag-handle',
    onUpdate: (event: SortableEvent) => {
        moveArrayElement(sortedCharacteristics, event.oldIndex ?? 0, event.newIndex ?? 0, event);

        // moveArrayElement() updates the list on the next tick.
        nextTick(() =>
            router.put(
                route('mappoint-categories.characteristics.reorder', props.categoryId),
                { ids: sortedCharacteristics.value.map((characteristic) => characteristic.id) },
                { preserveScroll: true },
            ),
        );
    },
});

const showDialog = ref(false);
const editedCharacteristic = ref<App.Data.MapPointCharacteristicData | null>(null);

function openDialog(characteristic: App.Data.MapPointCharacteristicData | null) {
    editedCharacteristic.value = characteristic;
    showDialog.value = true;
}

function editFields(characteristic: App.Data.MapPointCharacteristicData) {
    router.post(route('mappoint-characteristics.fields.edit', characteristic.id));
}

function deleteCharacteristic(characteristic: App.Data.MapPointCharacteristicData) {
    const count = characteristic.map_points_count ?? 0;
    const consequence =
        count === 0
            ? 'Sie ist noch keinem Punkt zugewiesen.'
            : `${count === 1 ? 'Ein Punkt verliert' : `${count} Punkte verlieren`} die Maßnahme. Ihre Werte bleiben an den Punkten als frühere Angaben erhalten.`;

    if (confirm(`Möchtest du die Maßnahme „${characteristic.name}“ löschen? ${consequence}`)) {
        router.delete(route('mappoint-characteristics.destroy', characteristic.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Card data-test="category-characteristics">
        <CardHeader class="flex flex-row items-start justify-between gap-2">
            <div class="space-y-1.5">
                <CardTitle>Maßnahmen</CardTitle>
                <CardDescription>
                    Wählbare Extras für die Punkte dieser Kategorie und ihrer Unterkategorien, z. B. ein Igeltor. Jede Maßnahme kann eigene Felder
                    haben, die Punkte mit dieser Maßnahme zusätzlich bekommen.
                </CardDescription>
            </div>
            <Button type="button" variant="outline" size="sm" @click="openDialog(null)">
                <Plus class="h-4 w-4" />
                Maßnahme
            </Button>
        </CardHeader>
        <CardContent class="space-y-3">
            <ul v-show="sortedCharacteristics.length > 0" ref="list" class="divide-y rounded-md border">
                <li
                    v-for="characteristic in sortedCharacteristics"
                    :key="characteristic.id"
                    class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm"
                    data-test="characteristic-row"
                >
                    <div class="flex items-center gap-2">
                        <GripVertical class="characteristic-drag-handle h-4 w-4 cursor-grab text-gray-400" />
                        <MapPointCharacteristicBadge :characteristic="characteristic" />
                        <span class="text-xs text-gray-500">
                            {{ characteristic.map_points_count === 1 ? '1 Punkt' : `${characteristic.map_points_count ?? 0} Punkte` }} ·
                            {{ (characteristic.fields ?? []).length === 1 ? '1 Feld' : `${(characteristic.fields ?? []).length} Felder` }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button type="button" variant="ghost" size="sm" @click="editFields(characteristic)">
                            <ListPlus class="h-4 w-4" />
                            Felder bearbeiten
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :aria-label="`${characteristic.name} bearbeiten`"
                            @click="openDialog(characteristic)"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :aria-label="`${characteristic.name} löschen`"
                            @click="deleteCharacteristic(characteristic)"
                        >
                            <Trash2 class="h-4 w-4 text-red-600" />
                        </Button>
                    </div>
                </li>
            </ul>
            <p v-if="sortedCharacteristics.length === 0" class="text-sm text-gray-500 italic">Diese Kategorie hat noch keine Maßnahmen.</p>

            <div v-if="inheritedCharacteristics.length > 0" class="space-y-1">
                <p class="text-xs font-medium text-gray-500">Ebenfalls wählbar, von Oberkategorien übernommen:</p>
                <div class="flex flex-wrap gap-1">
                    <MapPointCharacteristicBadge
                        v-for="characteristic in inheritedCharacteristics"
                        :key="characteristic.id"
                        :characteristic="characteristic"
                        :title="`Aus ${characteristic.category_name}`"
                    />
                </div>
            </div>
        </CardContent>

        <MapPointCharacteristicDialog v-model:open="showDialog" :category-id="categoryId" :characteristic="editedCharacteristic" />
    </Card>
</template>
