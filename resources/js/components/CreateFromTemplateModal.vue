<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { FormControl, FormField, FormItem, FormLabel } from '@/shadcn/components/ui/form';
import { Select } from '@/shadcn/components/ui/select';
import SelectContent from '@/shadcn/components/ui/select/SelectContent.vue';
import SelectItem from '@/shadcn/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/shadcn/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/shadcn/components/ui/select/SelectValue.vue';
import { router } from '@inertiajs/vue3';
import { flattenCategoryTree } from '@/utils/categoryTree';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type GroupData = App.Data.GroupData;

const props = withDefaults(
    defineProps<{
        templateType: 'advice' | 'map_point';
        groups: GroupData[];
        /** Only for map point forms: the categories points can be created in. */
        categories?: Array<App.Data.MapPointCategoryData>;
        usableCategoryIdsByGroup?: Record<string, Array<string>>;
        initialGroupId?: string;
        initialCategoryId?: string;
    }>(),
    { categories: () => [], usableCategoryIdsByGroup: () => ({}), initialGroupId: undefined, initialCategoryId: undefined },
);

const open = defineModel<boolean>('open', { required: true });
const selectedGroupId = ref<string | undefined>(props.initialGroupId);

/** Reka's select cannot hold null, so „no category“ gets its own value. */
const NO_CATEGORY = 'none';
const selectedCategoryId = ref<string>(props.initialCategoryId ?? NO_CATEGORY);

const categoryEntries = computed(() => {
    const usableIds = selectedGroupId.value ? (props.usableCategoryIdsByGroup[selectedGroupId.value] ?? []) : [];

    return flattenCategoryTree(props.categories.filter((category) => usableIds.includes(category.id)));
});

watch(categoryEntries, (entries) => {
    if (selectedCategoryId.value !== NO_CATEGORY && !entries.some(({ category }) => category.id === selectedCategoryId.value)) {
        selectedCategoryId.value = NO_CATEGORY;
    }
});

const templateTitles = {
    advice: 'Beratungsformular erstellen',
    map_point: 'Kartenpunkt-Formular erstellen',
};

const templateDescriptions = {
    advice: 'Erstellt automatisch ein vorkonfiguriertes Beratungsformular mit allen Standard-Feldern (Vorname, Nachname, Adresse, E-Mail, Telefon, Beratungstyp).',
    map_point:
        'Erstellt ein Formular, aus dem Kartenpunkte entstehen: mit Titel, Beschreibung und Standort. Mit einer Kategorie fragt es auch nach ihren Zusatzfeldern, alles ist schon zugeordnet.',
};

function handleCreate() {
    if (!selectedGroupId.value) {
        toast.error('Bitte wähle eine Initiative aus.');
        return;
    }

    router.post(
        route('form-definitions.from-template'),
        {
            template_type: props.templateType,
            group_id: selectedGroupId.value,
            map_point_category_id: props.templateType === 'map_point' && selectedCategoryId.value !== NO_CATEGORY ? selectedCategoryId.value : null,
        },
        {
            onSuccess: () => {
                toast.success('Formular wurde erfolgreich erstellt!');
                open.value = false;
            },
            onError: (errors) => {
                toast.error(`Fehler: ${Object.values(errors).join(', ')}`);
            },
        },
    );
}

function handleCancel() {
    open.value = false;
    selectedGroupId.value = undefined;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ templateTitles[templateType] }}</DialogTitle>
                <DialogDescription>
                    {{ templateDescriptions[templateType] }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <FormField v-slot="{ componentField }" name="group_id">
                    <FormItem>
                        <FormLabel>Initiative *</FormLabel>
                        <FormControl>
                            <Select v-model="selectedGroupId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Wähle eine Initiative" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                    </FormItem>
                </FormField>

                <FormItem v-if="templateType === 'map_point'">
                    <FormLabel>Kategorie der Kartenpunkte</FormLabel>
                    <Select v-model="selectedCategoryId">
                        <SelectTrigger data-test="template-category">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NO_CATEGORY">Keine Kategorie</SelectItem>
                            <SelectItem
                                v-for="{ category, depth } in categoryEntries"
                                :key="category.id"
                                :value="category.id"
                                :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormItem>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleCancel">Abbrechen</Button>
                <Button @click="handleCreate">Erstellen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
