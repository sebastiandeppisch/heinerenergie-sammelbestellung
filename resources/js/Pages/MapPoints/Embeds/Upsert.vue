<script setup lang="ts">
import CategorizedPointsMap from '@/components/CategorizedPointsMap.vue';
import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import MapEmbedDialog from '@/components/MapEmbedDialog.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Switch } from '@/shadcn/components/ui/switch';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapEmbed?: App.Data.MapEmbedData;
    categories: Array<App.Data.MapPointCategoryData>;
    pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
}>();

const isEditing = computed(() => !!props.mapEmbed);

const darmstadt = { lat: 49.8728, lng: 8.6512 };

const selectedCategoryIds = new Set(props.mapEmbed?.categories.map((category) => category.id) ?? []);
const categorySelection = reactive<Record<string, boolean>>(
    Object.fromEntries(props.categories.map((category) => [category.id, selectedCategoryIds.has(category.id)])),
);

const form = useForm({
    name: props.mapEmbed?.name || '',
    category_ids: props.mapEmbed?.categories.map((category) => category.id) || [],
    coordinate: props.mapEmbed?.coordinate || darmstadt,
    zoom: props.mapEmbed?.zoom ?? 15,
    show_table: props.mapEmbed?.show_table ?? true,
    aspect_ratio_width: props.mapEmbed?.aspect_ratio_width ?? 16,
    aspect_ratio_height: props.mapEmbed?.aspect_ratio_height ?? 9,
});

const aspectRatioPresets = [
    { label: 'Breit (16:9)', width: 16, height: 9 },
    { label: 'Klassisch (4:3)', width: 4, height: 3 },
    { label: 'Quadratisch (1:1)', width: 1, height: 1 },
    { label: 'Hochformat (3:4)', width: 3, height: 4 },
    { label: 'Sehr breit (21:9)', width: 21, height: 9 },
];

const aspectRatioPreset = computed({
    get: () => `${form.aspect_ratio_width}:${form.aspect_ratio_height}`,
    set: (value: string) => {
        const [width, height] = value.split(':').map(Number);
        form.aspect_ratio_width = width;
        form.aspect_ratio_height = height;
    },
});

const previewCategories = computed(() => props.categories.filter((category) => categorySelection[category.id]));

const previewBoxStyle = computed(() => ({
    aspectRatio: `${form.aspect_ratio_width} / ${form.aspect_ratio_height}`,
    minHeight: '280px',
    maxHeight: '520px',
}));

watch(
    categorySelection,
    () => {
        form.category_ids = Object.entries(categorySelection)
            .filter(([, checked]) => checked)
            .map(([categoryId]) => categoryId);
    },
    { deep: true },
);

function submit() {
    if (isEditing.value && props.mapEmbed) {
        form.put(route('map-embeds.update', props.mapEmbed.id));
    } else {
        form.post(route('map-embeds.store'));
    }
}
</script>

<template>
    <div class="container mx-auto py-8">
        <div class="mx-auto mb-4 max-w-2xl">
            <Button variant="outline" @click="router.visit(route('map-embeds.index'))">
                <ArrowLeft />
                Zurück
            </Button>
        </div>

        <Card class="mx-auto max-w-2xl">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>{{ isEditing ? 'Einbettung bearbeiten' : 'Neue Einbettung erstellen' }}</CardTitle>
                <div v-if="isEditing && mapEmbed" class="flex gap-2">
                    <Button as="a" :href="route('map.public', mapEmbed.id)" target="_blank" rel="noopener noreferrer" variant="outline">
                        Link öffnen
                        <ExternalLink />
                    </Button>
                    <MapEmbedDialog :map-embed="mapEmbed" />
                </div>
            </CardHeader>
            <form @submit.prevent="submit">
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="z. B. Startseite Sidebar" />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        <p class="text-xs text-gray-500">Dient nur zur Wiedererkennung im Backend, wird nicht auf der Karte angezeigt.</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Kategorien</Label>
                        <p class="text-xs text-gray-500">
                            Wähle aus, welche Kategorien von Punkten in dieser Einbettung angezeigt werden. Du kannst die Auswahl später ändern, ohne
                            dass sich der Einbettungslink ändert.
                        </p>
                        <div class="space-y-2 rounded-lg border p-3">
                            <CategoryVisibilityFilter v-model:visibility="categorySelection" :categories="categories" id-prefix="embed-category-" />
                            <p v-if="categories.length === 0" class="text-sm text-gray-500 italic">Es wurden noch keine Kategorien angelegt.</p>
                        </div>
                        <p v-if="form.errors.category_ids" class="text-sm text-red-500">{{ form.errors.category_ids }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="show_table" v-model="form.show_table" />
                        <Label for="show_table">Tabellen-Ansicht anbieten</Label>
                        <p v-if="form.errors.show_table" class="text-sm text-red-500">{{ form.errors.show_table }}</p>
                    </div>
                    <p class="-mt-2 text-xs text-gray-500">
                        Wenn aktiv, können Besucher:innen der Einbettung über Tabs zwischen Karte und einer durchsuchbaren Tabelle wechseln.
                    </p>

                    <div class="space-y-2">
                        <Label for="aspect_ratio">Seitenverhältnis</Label>
                        <Select v-model="aspectRatioPreset">
                            <SelectTrigger id="aspect_ratio">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="preset in aspectRatioPresets" :key="preset.label" :value="`${preset.width}:${preset.height}`">
                                    {{ preset.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.aspect_ratio_width || form.errors.aspect_ratio_height" class="text-sm text-red-500">
                            {{ form.errors.aspect_ratio_width || form.errors.aspect_ratio_height }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Legt fest, wie hoch Karte und Tabelle im Verhältnis zu ihrer Breite dargestellt werden. Beide Ansichten nutzen dasselbe
                            Verhältnis, damit der Inhalt beim Wechseln zwischen den Tabs gleich hoch bleibt.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>Vorschau</Label>
                        <p class="text-xs text-gray-500">
                            Ziehe und zoome die Karte, um den Kartenausschnitt festzulegen, der beim Einbetten angezeigt wird. Die Vorschau zeigt das
                            oben gewählte Seitenverhältnis.
                        </p>
                        <div class="w-full overflow-hidden rounded-lg border" :style="previewBoxStyle">
                            <CategorizedPointsMap
                                :key="aspectRatioPreset"
                                v-model:center="form.coordinate"
                                v-model:zoom="form.zoom"
                                :points-by-category="pointsByCategory"
                                :categories="previewCategories"
                            />
                        </div>
                        <p v-if="form.errors.coordinate" class="text-sm text-red-500">{{ form.errors.coordinate }}</p>
                        <p v-if="form.errors.zoom" class="text-sm text-red-500">{{ form.errors.zoom }}</p>
                    </div>
                </CardContent>

                <CardFooter class="mt-4 flex justify-between">
                    <div></div>
                    <Button type="submit" :disabled="form.processing || (isEditing && !form.isDirty)">
                        {{ isEditing ? 'Einbettung aktualisieren' : 'Einbettung erstellen' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
