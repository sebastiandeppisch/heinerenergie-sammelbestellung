<script setup lang="ts">
import PolygonMap from '@/components/PolygonMap.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/shadcn/components/ui/accordion';
import { Button } from '@/shadcn/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { ArrowLeft, MapPinned, PencilRuler, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import PostalCodeAreaPanel from './PostalCodeAreaPanel.vue';

const props = defineProps<{
    group: App.Data.GroupData;
    polygon: App.ValueObjects.Polygon;
    consultingAreaPostalCodes: string[];
}>();

const form = useForm({
    polygon: props.polygon || [],
    // The postal codes the area was built from. They stay while the area is
    // reshaped by hand, because they describe its basis, not its exact outline.
    postal_codes: [...props.consultingAreaPostalCodes],
});

const hasChanges = ref(false);
const mapRef = ref<InstanceType<typeof PolygonMap> | null>(null);

const hasArea = computed(() => (form.polygon as App.ValueObjects.Polygon)?.coordinates?.length > 0);

// As long as there is no area, both ways to create one are offered side by side.
const startedWith = ref<'postal-codes' | 'drawing' | null>(null);
const showChoice = computed(() => !hasArea.value && startedWith.value === null);

watch(hasArea, (areaExists) => {
    if (!areaExists) {
        startedWith.value = null;
    }
});

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

watch(
    () => props.consultingAreaPostalCodes,
    (newPostalCodes) => {
        form.postal_codes = [...newPostalCodes];
    },
    { deep: true },
);

const startDrawing = () => {
    startedWith.value = 'drawing';
    mapRef.value?.startDrawing();
};

// A freshly drawn area replaces the basis, so the postal codes no longer apply.
const handleDrawn = () => {
    form.postal_codes = [];
};

const handlePostalCodeArea = (polygon: App.ValueObjects.Polygon) => {
    form.polygon = polygon;
    mapRef.value?.centerAndZoomToContent();
};

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
        <div v-if="showChoice" class="grid gap-4 pt-2 sm:grid-cols-2">
            <button
                type="button"
                class="flex flex-col items-start gap-2 rounded-lg border border-input p-4 text-left transition hover:border-primary hover:bg-accent"
                @click="startedWith = 'postal-codes'"
            >
                <MapPinned class="h-5 w-5 text-primary" />
                <span class="font-medium">Aus Postleitzahlen erstellen</span>
                <span class="text-sm text-gray-600">
                    Gib die Postleitzahlen deines Gebiets an, die Grenzen kommen aus OpenStreetMap. Der schnellste Weg zu einem Beratungsgebiet.
                </span>
            </button>

            <button
                type="button"
                class="flex flex-col items-start gap-2 rounded-lg border border-input p-4 text-left transition hover:border-primary hover:bg-accent"
                @click="startDrawing"
            >
                <PencilRuler class="h-5 w-5 text-primary" />
                <span class="font-medium">Selbst zeichnen</span>
                <span class="text-sm text-gray-600">
                    Setze die Eckpunkte deines Gebiets von Hand in die Karte. Aufwändiger, dafür beliebig genau.
                </span>
            </button>
        </div>

        <div v-else-if="!hasArea && startedWith === 'postal-codes'" class="rounded-lg border border-input p-4">
            <PostalCodeAreaPanel v-model:postal-codes="form.postal_codes" :group="group" :has-area="hasArea" @loaded="handlePostalCodeArea" />

            <Button variant="ghost" size="sm" class="mt-3 -ml-2" @click="startedWith = null">
                <ArrowLeft class="h-4 w-4" />
                Zurück zur Auswahl
            </Button>
        </div>

        <Accordion v-else-if="hasArea" type="single" collapsible>
            <AccordionItem value="postal-codes" class="border-b-0">
                <AccordionTrigger class="text-sm">Gebiet aus Postleitzahlen laden</AccordionTrigger>
                <AccordionContent>
                    <PostalCodeAreaPanel v-model:postal-codes="form.postal_codes" :group="group" :has-area="hasArea" @loaded="handlePostalCodeArea" />
                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <p v-if="hasArea || startedWith === 'drawing'" class="text-right text-sm text-gray-600">
            Klicke auf das Polygon-Symbol oben rechts in der Karte, um den Beratungsbereich zu zeichnen, oder auf das Stift-Symbol, um ihn zu
            bearbeiten.
        </p>

        <div class="h-[600px] w-full">
            <PolygonMap ref="mapRef" v-model="form.polygon" class="rounded-lg" :logo="group.logo_path" :logo-aspect="2.84" @drawn="handleDrawn" />
        </div>

        <div class="mt-4 flex justify-between">
            <div>
                <Button v-if="hasArea" variant="outline" :disabled="form.processing" @click="handleDelete">
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
