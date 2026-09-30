<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter } from '@/shadcn/components/ui/card';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import type { CustomPageProps } from '@/types/pageProps';
import { descendantIds, flattenCategoryTree } from '@/utils/categoryTree';
import { router, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { ListPlus, Upload } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    category?: App.Data.MapPointCategoryData;
    groups: Array<App.Data.GroupBaseData>;
    categories: Array<App.Data.MapPointCategoryData>;
    usableCategoryIdsByGroup: Record<string, Array<string>>;
    /** The fields of the category's points, inherited ones first. Only sent when editing. */
    fields?: Array<App.Data.MapPointCategoryFieldData>;
}>();

const page = usePage<CustomPageProps>();

const isEditing = computed(() => !!props.category);

setLayoutProps({
    breadcrumbs: [
        { title: 'Kartenpunkte', href: route('mappoints.index') },
        { title: 'Kategorien', href: route('mappoint-categories.index') },
        { title: isEditing.value ? 'Bearbeiten' : 'Neu' },
    ],
});
const fileInput = ref<HTMLInputElement>();

const form = useForm({
    name: props.category?.name || '',
    /** The initiative is only chosen on creation, new categories default to the current initiative. */
    group_id: props.category?.group_id ?? page.props.auth.currentGroup?.id ?? props.groups[0]?.id ?? null,
    parent_id: props.category?.parent_id ?? (null as string | null),
    image: null as File | null,
    public_field_ids: (props.fields ?? []).filter((field) => field.category_id === props.category?.id && field.is_public).map((field) => field.id),
    _method: isEditing.value ? 'put' : 'post',
});

const ownFields = computed(() => (props.fields ?? []).filter((field) => field.category_id === props.category?.id));
const inheritedFields = computed(() => (props.fields ?? []).filter((field) => field.category_id !== props.category?.id));

function isPublic(fieldId: string): boolean {
    return form.public_field_ids.includes(fieldId);
}

function setPublic(fieldId: string, isPublic: boolean | 'indeterminate') {
    form.public_field_ids = isPublic === true ? [...form.public_field_ids, fieldId] : form.public_field_ids.filter((id) => id !== fieldId);
}

function editFields() {
    router.post(route('mappoint-categories.fields.edit', props.category!.id));
}

/** Reka's select cannot hold null, so „no parent“ gets its own value. */
const NO_PARENT = 'none';

const parentSelection = computed({
    get: () => form.parent_id ?? NO_PARENT,
    set: (value: string) => {
        form.parent_id = value === NO_PARENT ? null : value;
    },
});

/** The parent must belong to the category's initiative or a parent initiative, and must not be the category itself or one of its sub categories. */
const parentCandidates = computed(() => {
    const usableCategoryIds = form.group_id ? (props.usableCategoryIdsByGroup[form.group_id] ?? []) : [];
    const excludedIds = props.category ? [props.category.id, ...descendantIds(props.categories, props.category.id)] : [];

    return flattenCategoryTree(props.categories.filter((category) => usableCategoryIds.includes(category.id) && !excludedIds.includes(category.id)));
});

watch(
    () => form.group_id,
    () => {
        if (form.parent_id !== null && !parentCandidates.value.some(({ category }) => category.id === form.parent_id)) {
            form.parent_id = null;
        }
    },
);

const imagePreviewUrl = computed(() => {
    if (form.image) {
        return URL.createObjectURL(form.image);
    }
    return null;
});

function submit() {
    if (isEditing.value) {
        form.post(route('mappoint-categories.update', props.category!.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('mappoint-categories.store'), {
            forceFormData: true,
        });
    }
}

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
    }
}

function triggerFileInput() {
    fileInput.value?.click();
}
</script>

<template>
    <div class="mx-auto w-full max-w-3xl">
        <PageHeader :title="isEditing ? 'Kategorie bearbeiten' : 'Neue Kategorie erstellen'" />

        <Card>
            <form @submit.prevent="submit">
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="group_id">Initiative</Label>
                        <Input v-if="isEditing" id="group_id" :model-value="category?.group_name" disabled />
                        <Select v-else id="group_id" v-model="form.group_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Wähle eine Initiative aus" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.group_id" class="text-sm text-red-500">{{ form.errors.group_id }}</p>
                        <p class="text-xs text-gray-500">
                            Untergeordnete Initiativen können die Kategorie ebenfalls nutzen. Die Initiative kann nach dem Anlegen nicht mehr geändert
                            werden.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="parent_id">Oberkategorie</Label>
                        <Select id="parent_id" v-model="parentSelection">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NO_PARENT">Keine (Hauptkategorie)</SelectItem>
                                <SelectItem
                                    v-for="{ category: candidate, depth } in parentCandidates"
                                    :key="candidate.id"
                                    :value="candidate.id"
                                    :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                                >
                                    {{ candidate.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.parent_id" class="text-sm text-red-500">{{ form.errors.parent_id }}</p>
                        <p class="text-xs text-gray-500">
                            Auf der Karte lassen sich Unterkategorien gemeinsam mit ihrer Oberkategorie ein- und ausblenden. Ohne eigenes Bild
                            übernimmt eine Unterkategorie das Bild ihrer Oberkategorie.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>Kategorie-Bild (Pin für Karte)</Label>

                        <!-- Current Image Display -->
                        <div v-if="isEditing && category?.image_path" class="mb-4">
                            <p class="mb-2 text-sm text-gray-600">Aktuelles Bild:</p>
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                <img :src="category.image_path" :alt="category.name" class="h-full w-full object-contain" />
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div v-if="form.image && imagePreviewUrl" class="mb-4">
                            <p class="mb-2 text-sm text-gray-600">Neue Bildvorschau:</p>
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                <img :src="imagePreviewUrl" alt="Vorschau" class="h-full w-full object-contain" />
                            </div>
                        </div>

                        <!-- File Input -->
                        <input ref="fileInput" type="file" accept="image/*" @change="handleFileSelect" class="hidden" />

                        <Button type="button" variant="outline" @click="triggerFileInput" class="w-full">
                            <Upload class="mr-2 h-4 w-4" />
                            {{ form.image ? 'Anderes Bild wählen' : isEditing && category?.image_path ? 'Bild ersetzen' : 'Bild hochladen' }}
                        </Button>

                        <p v-if="form.errors.image" class="text-sm text-red-500">{{ form.errors.image }}</p>
                        <p class="text-xs text-gray-500">
                            Das Bild wird als Pin-Symbol auf der Karte verwendet. Empfohlen: Quadratisches Format, mindestens 32x32 Pixel.
                        </p>
                    </div>

                    <div v-if="isEditing" class="space-y-3" data-test="category-fields">
                        <div class="flex items-center justify-between gap-2">
                            <Label>Zusatzfelder</Label>
                            <Button type="button" variant="outline" size="sm" :disabled="form.isDirty" @click="editFields">
                                <ListPlus class="h-4 w-4" />
                                Felder bearbeiten
                            </Button>
                        </div>
                        <p class="text-xs text-gray-500">
                            Zusätzliche Angaben für die Punkte dieser Kategorie, z. B. PV-Leistung. Unterkategorien ohne eigene Felder übernehmen die
                            Felder. Eigene Felder einer Unterkategorie ersetzen die übernommenen, sie werden nicht ergänzt. Alle Felder sind
                            freiwillig. Nur Felder mit Haken bei „öffentlich“ erscheinen auf der öffentlichen Karte, alle anderen bleiben intern, etwa
                            für Kontaktdaten.
                        </p>
                        <p v-if="form.isDirty" class="text-xs text-amber-600">
                            Speichere zuerst deine Änderungen, dann kannst du die Felder bearbeiten.
                        </p>

                        <ul v-if="ownFields.length > 0" class="divide-y rounded-md border">
                            <li v-for="field in ownFields" :key="field.id" class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                                <span>{{ field.label }}</span>
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        :id="`public_${field.id}`"
                                        :model-value="isPublic(field.id)"
                                        @update:model-value="(checked) => setPublic(field.id, checked)"
                                    />
                                    <Label :for="`public_${field.id}`" class="font-normal">öffentlich</Label>
                                </div>
                            </li>
                        </ul>
                        <p v-else-if="inheritedFields.length === 0" class="text-sm text-gray-500 italic">Diese Kategorie hat noch keine Felder.</p>

                        <div v-if="inheritedFields.length > 0" class="space-y-1">
                            <p class="text-xs font-medium text-gray-500">
                                Übernommen von der Oberkategorie {{ inheritedFields[0].category_name }}. Die Sichtbarkeit legst du dort fest.
                            </p>
                            <ul class="divide-y rounded-md border bg-muted/30">
                                <li
                                    v-for="field in inheritedFields"
                                    :key="field.id"
                                    class="flex items-center justify-between gap-2 px-3 py-2 text-sm"
                                >
                                    <span>{{ field.label }}</span>
                                    <span class="text-xs text-gray-500">{{ field.is_public ? 'öffentlich' : 'intern' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="mt-6 flex justify-between">
                    <div></div>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Kategorie aktualisieren' : 'Kategorie erstellen' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
