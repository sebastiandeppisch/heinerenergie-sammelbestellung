<script setup lang="ts">
import PolygonMap from '@/components/PolygonMap.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { Save, Trash2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    group: App.Data.GroupData;
    polygon: App.ValueObjects.Polygon;
}>();

const form = useForm({
    polygon: props.polygon || [],
});

const hasChanges = ref(false);

watch(
    () => form.polygon,
    (newPolygon) => {
        hasChanges.value = JSON.stringify(newPolygon) !== JSON.stringify(props.polygon || []);
    },
    { deep: true },
);

watch(
    () => props.polygon,
    (newPolygon) => {
        form.polygon = newPolygon || [];
    },
    { deep: true },
);

const handleSave = () => {
    form.post(route('groups.consulting-area.update', props.group.id), {
        preserveScroll: true,
        onSuccess: () => {
            hasChanges.value = false;
        },
    });
};

const handleDelete = () => {
    form.delete(route('groups.consulting-area.delete', props.group.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="mt-4">
            <p class="text-right text-sm text-gray-600">
                Klicke auf das Polygon-Symbol oben rechts in der Karte, um den Beratungsbereich zu bearbeiten.
            </p>
        </div>

        <div class="h-[600px] w-full">
            <PolygonMap v-model="form.polygon" class="rounded-lg" :logo="group.logo_path" :logo-aspect="2.84" />
        </div>

        <div class="mt-4 flex justify-between">
            <div>
                <Button
                    v-if="props.polygon"
                    variant="outline"
                    :disabled="form.processing || form.polygon.coordinates.length === 0"
                    @click="handleDelete"
                >
                    <Trash2 class="h-4 w-4" />
                    Beratungsgebiet löschen
                </Button>
            </div>
            <div>
                <Button variant="default" :disabled="form.processing || !hasChanges" @click="handleSave">
                    <Save class="h-4 w-4" />
                    Beratungsgebiet speichern
                </Button>
            </div>
        </div>
    </div>
</template>
