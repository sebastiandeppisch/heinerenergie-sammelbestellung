<script setup lang="ts">
import Button from '@/shadcn/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';
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
        /** The fields of each category, which the follow-up form can take over from the first form. */
        fieldsByCategory?: Record<string, Array<App.Data.FormFieldData>>;
        initialGroupId?: string;
        initialCategoryId?: string;
    }>(),
    {
        categories: () => [],
        usableCategoryIdsByGroup: () => ({}),
        fieldsByCategory: () => ({}),
        initialGroupId: undefined,
        initialCategoryId: undefined,
    },
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

const withFollowUp = ref(false);
const followUpOnlyFieldIds = ref<string[]>([]);

const categoryFields = computed(() => (selectedCategoryId.value === NO_CATEGORY ? [] : (props.fieldsByCategory[selectedCategoryId.value] ?? [])));

watch(selectedCategoryId, (categoryId) => {
    followUpOnlyFieldIds.value = [];

    if (categoryId === NO_CATEGORY) {
        withFollowUp.value = false;
    }
});

function setFollowUpOnly(fieldId: string, checked: boolean) {
    followUpOnlyFieldIds.value = checked ? [...followUpOnlyFieldIds.value, fieldId] : followUpOnlyFieldIds.value.filter((id) => id !== fieldId);
}

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
            with_follow_up: props.templateType === 'map_point' && withFollowUp.value,
            follow_up_only_field_ids: withFollowUp.value ? followUpOnlyFieldIds.value : [],
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
                <div class="grid gap-2">
                    <Label for="template-group">Initiative *</Label>
                    <Select v-model="selectedGroupId">
                        <SelectTrigger id="template-group">
                            <SelectValue placeholder="Wähle eine Initiative" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                {{ group.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="templateType === 'map_point'" class="grid gap-2">
                    <Label for="template-category">Kategorie der Kartenpunkte</Label>
                    <Select v-model="selectedCategoryId">
                        <SelectTrigger id="template-category" data-test="template-category">
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
                </div>

                <div v-if="templateType === 'map_point' && selectedCategoryId !== NO_CATEGORY" class="grid gap-2">
                    <div class="flex items-start gap-2">
                        <Checkbox id="template-follow-up" v-model="withFollowUp" data-test="template-follow-up" />
                        <Label for="template-follow-up" class="text-sm font-normal">Folgeformular „Erzähl mehr“ anlegen</Label>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Mit dem Folgeformular ergänzt die Person ihren Eintrag später, z. B. mit Fotos. Es fragt nach allen Feldern und Maßnahmen der
                        Kategorie und zeigt, was schon eingetragen ist.
                    </p>

                    <div v-if="withFollowUp && categoryFields.length > 0" class="grid gap-2 rounded-md border p-3">
                        <p class="text-sm font-medium">Nur im Folgeformular fragen</p>
                        <div v-for="field in categoryFields" :key="field.id" class="flex items-start gap-2">
                            <Checkbox
                                :id="`template-follow-up-only-${field.id}`"
                                :model-value="followUpOnlyFieldIds.includes(field.id)"
                                :data-test="`template-follow-up-only-${field.id}`"
                                @update:model-value="(checked) => setFollowUpOnly(field.id, checked === true)"
                            />
                            <Label :for="`template-follow-up-only-${field.id}`" class="text-sm font-normal">{{ field.label }}</Label>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleCancel">Abbrechen</Button>
                <Button @click="handleCreate">Erstellen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
