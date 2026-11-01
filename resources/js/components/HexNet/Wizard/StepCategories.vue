<script setup lang="ts">
import CategoryVisibilityFilter from '@/components/CategoryVisibilityFilter.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shadcn/components/ui/tabs';
import { isIncludedByAncestor } from '@/utils/categoryTree';
import { useForm } from '@inertiajs/vue3';
import { Plus, Sparkles, Trash2 } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    hexNet: App.HexNet.Data.HexNetData;
    categories: Array<App.Data.MapPointCategoryData>;
    defaults: { main_category: string; sub_category: string; measures: Array<{ name: string; color: string }> };
}>();

const emit = defineEmits<{ saved: [] }>();

const mode = ref(props.hexNet.category_ids.length > 0 ? 'existing' : 'template');

const template = useForm({
    main_name: props.defaults.main_category,
    sub_name: props.defaults.sub_category,
    measures: props.defaults.measures.map((measure) => ({ ...measure })),
});

const selection = reactive<Record<string, boolean>>(Object.fromEntries(props.hexNet.category_ids.map((id) => [id, true])));
const selectedIds = computed(() =>
    Object.entries(selection)
        .filter(([id, checked]) => checked && !isIncludedByAncestor(props.categories, selection, id))
        .map(([id]) => id),
);
const existing = useForm({ category_ids: [] as string[] });

const selectedNames = computed(() =>
    props.categories.filter((category) => props.hexNet.category_ids.includes(category.id)).map((category) => category.name),
);

function createFromTemplate() {
    template.post(route('hexnets.setup-categories', props.hexNet.id), {
        preserveScroll: true,
        onSuccess: () => {
            mode.value = 'existing';
            emit('saved');
        },
    });
}

function saveExisting() {
    existing.category_ids = selectedIds.value;
    existing.put(route('hexnets.update-step', [props.hexNet.id, 'categories']), { preserveScroll: true, onSuccess: () => emit('saved') });
}

function addMeasure() {
    template.measures.push({ name: '', color: '#7cb342' });
}

watch(
    () => props.hexNet.category_ids,
    (ids) => ids.forEach((id) => (selection[id] = true)),
);
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-muted-foreground">
            Alle Kartenpunkte dieser Kategorien (inklusive Unterkategorien) zählen als Trittsteine. Die Kategorien müssen aus einem Teilbaum stammen
            und dieselben Maßnahmen haben.
        </p>
        <div v-if="selectedNames.length" class="rounded-md border border-green-200 bg-green-50 p-3 text-sm">
            Aktuell zählen: <b>{{ selectedNames.join(', ') }}</b>
        </div>

        <Tabs v-model="mode">
            <TabsList>
                <TabsTrigger value="template"><Sparkles class="mr-1 h-4 w-4" /> Aus Vorlage anlegen</TabsTrigger>
                <TabsTrigger value="existing">Vorhandene wählen</TabsTrigger>
            </TabsList>

            <TabsContent value="template" class="space-y-4 pt-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="main_name">Hauptkategorie (trägt die Maßnahmen)</Label>
                        <Input id="main_name" v-model="template.main_name" />
                        <p v-if="template.errors.main_name" class="text-sm text-red-500">{{ template.errors.main_name }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label for="sub_name">Kategorie dieser Kampagne</Label>
                        <Input id="sub_name" v-model="template.sub_name" />
                        <p v-if="template.errors.sub_name" class="text-sm text-red-500">{{ template.errors.sub_name }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Maßnahmen</Label>
                    <div v-for="(measure, index) in template.measures" :key="index" class="flex items-center gap-2">
                        <input
                            v-model="measure.color"
                            type="color"
                            class="h-9 w-10 cursor-pointer rounded border"
                            :aria-label="`Farbe ${measure.name}`"
                        />
                        <Input v-model="measure.name" class="flex-1" />
                        <Button variant="ghost" size="sm" @click="template.measures.splice(index, 1)"><Trash2 class="h-4 w-4" /></Button>
                    </div>
                    <Button variant="outline" size="sm" @click="addMeasure"><Plus class="mr-1 h-4 w-4" /> Maßnahme</Button>
                    <p v-if="template.errors.measures" class="text-sm text-red-500">{{ template.errors.measures }}</p>
                </div>
                <Button :disabled="template.processing" @click="createFromTemplate">Kategorien &amp; Maßnahmen anlegen</Button>
                <p class="text-xs text-muted-foreground">Gibt es die Hauptkategorie schon, werden nur fehlende Maßnahmen ergänzt.</p>
            </TabsContent>

            <TabsContent value="existing" class="space-y-4 pt-2">
                <CategoryVisibilityFilter v-model:visibility="selection" :categories="categories" id-prefix="hexnet-category-" include-descendants />
                <p v-if="categories.length === 0" class="text-sm text-muted-foreground">
                    Es gibt noch keine Kategorien. Lege sie aus der Vorlage an.
                </p>
                <p v-if="existing.errors.category_ids" class="text-sm text-red-500">{{ existing.errors.category_ids }}</p>
                <Button :disabled="existing.processing || selectedIds.length === 0" @click="saveExisting">Auswahl speichern</Button>
            </TabsContent>
        </Tabs>
    </div>
</template>
