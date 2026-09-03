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
import type { CustomPageProps } from '@/types/pageProps';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { propsToLeafletOptions } from '@vue-leaflet/vue-leaflet/dist/src/utils';
import { ArrowLeft, ExternalLink } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapEmbed?: App.Data.MapEmbedData;
    categories: Array<App.Data.MapPointCategoryData>;
    pointsByCategory: Record<string, Array<App.Data.MapPointData>>;
    groups: Array<App.Data.GroupBaseData>;
}>();

const isEditing = computed(() => !!props.mapEmbed);

const page = usePage<CustomPageProps>();


/** New embeds default to the initiative the admin is currently acting for. */

function initialGroupId(){
    if(!props.mapEmbed){
        if(! page.props.auth.currentGroup){
            return null;
        }
        return page.props.auth.currentGroup.id;
    }
    return props.mapEmbed.group_id;
}

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
    group_id: initialGroupId(),
});

const previewCategories = computed(() => props.categories.filter((category) => categorySelection[category.id]));

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
    form.transform((data) => ({
        ...data
    }));

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
                        <Label for="group_id">Initiative</Label>
                        <Select id="group_id" v-model="form.group_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Wähle eine Initiative aus" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Keine Initiative</SelectItem>
                                <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.group_id" class="text-sm text-red-500">{{ form.errors.group_id }}</p>
                        <p class="text-xs text-gray-500">
                            Die Primärfarbe dieser Initiative wird auf der eingebetteten Karte verwendet. Ohne Initiative bleibt es bei der
                            Standardfarbe.
                        </p>
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
                        <Label>Vorschau</Label>
                        <p class="text-xs text-gray-500">
                            Ziehe und zoome die Karte, um den Kartenausschnitt festzulegen, der beim Einbetten angezeigt wird.
                        </p>
                        <div class="h-[400px] w-full overflow-hidden rounded-lg border">
                            <CategorizedPointsMap
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
