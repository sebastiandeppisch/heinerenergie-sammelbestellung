<script setup lang="ts">
import PolygonMap from '@/components/PolygonMap.vue';
import { useForm } from '@inertiajs/vue3';
import DxButton from 'devextreme-vue/button';
import { ref, watch } from 'vue';
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
                <DxButton
                    v-if="props.polygon"
                    text="Beratungsgebiet löschen"
                    :disabled="form.processing || form.polygon.coordinates.length === 0"
                    stylingMode="outlined"
                    icon="trash"
                    type="danger"
                    @click="handleDelete"
                />
            </div>
            <div>
                <DxButton
                    text="Beratungsgebiet speichern"
                    :disabled="form.processing || !hasChanges"
                    stylingMode="contained"
                    @click="handleSave"
                    type="default"
                />
            </div>
        </div>
    </div>
</template>
