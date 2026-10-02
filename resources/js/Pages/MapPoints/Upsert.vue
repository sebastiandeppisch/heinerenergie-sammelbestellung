<script setup lang="ts">
import FormFieldInputRenderer from '@/components/FormBuilder/FormFieldInputRenderer.vue';
import MapPointFieldList from '@/components/MapPointFieldList.vue';
import MapPointImageInput from '@/components/MapPointImageInput.vue';
import PageHeader from '@/components/PageHeader.vue';
import PinLocationMap from '@/components/PinLocationMap.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Switch } from '@/shadcn/components/ui/switch';
import { Textarea } from '@/shadcn/components/ui/textarea';
import type { CustomPageProps } from '@/types/pageProps';
import { flattenCategoryTree } from '@/utils/categoryTree';
import { setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPoint?: App.Data.MapPointData;
    categories?: Array<App.Data.MapPointCategoryData>;
    groups: Array<App.Data.GroupBaseData>;
    usableCategoryIdsByGroup: Record<string, Array<string>>;
    /** The fields of each category including the inherited ones, keyed by category id. */
    fieldsByCategory: Record<string, Array<App.Data.FormFieldData>>;
    publicFieldIds: Array<string>;
}>();

const isEditing = !!props.mapPoint;

setLayoutProps({
    breadcrumbs: [{ title: 'Kartenpunkte', href: route('mappoints.index') }, { title: isEditing ? 'Bearbeiten' : 'Neu' }],
});

const page = usePage<CustomPageProps>();

const defaultMapPoint: App.Data.MapPointData = {
    id: '',
    title: '',
    description: '',
    coordinate: { lat: 0, lng: 0 },
    published: false,
    userReadablePointableType: '',
    created_at: '',
    /** New points default to the initiative the admin is currently acting for. */
    group_id: page.props.auth.currentGroup?.id ?? props.groups[0]?.id ?? '',
    category_id: null,
    location: null,
    fields: [],
    former_fields: [],
};

const initialPoint = props.mapPoint || defaultMapPoint;

type FieldValue = App.Data.MapPointFieldValueData['value'] | Array<string | File>;

/** Image fields hold the file names of their images, which keep them when saving. */
function initialValue(field: App.Data.MapPointFieldValueData): FieldValue {
    return field.type === 'image' ? field.images.map((image) => image.name) : field.value;
}

const form = useForm<App.Data.MapPointData & { field_values: Record<string, FieldValue> }>({
    ...initialPoint,
    /** Keyed by field id. Values of fields the chosen category does not have are ignored by the server. */
    field_values: Object.fromEntries(
        initialPoint.fields.filter((field) => field.field_id !== null).map((field) => [field.field_id!, initialValue(field)]),
    ),
});

function imageEntries(fieldId: string): Array<string | File> {
    const value = form.field_values[fieldId];
    return Array.isArray(value) ? value : [];
}

function storedImages(fieldId: string): Array<App.Data.ImageData> {
    return initialPoint.fields.find((field) => field.field_id === fieldId)?.images ?? [];
}

const categoryFields = computed(() => (form.category_id ? (props.fieldsByCategory[form.category_id] ?? []) : []));

/** Errors of single images are reported for the field. */
function fieldError(fieldId: string): string | undefined {
    const errors = form.errors as Record<string, string>;
    const key = Object.keys(errors).find((key) => key === `field_values.${fieldId}` || key.startsWith(`field_values.${fieldId}.`));

    return key ? errors[key] : undefined;
}

/** Only categories of the selected initiative and its parent initiatives can be assigned. */
const availableCategories = computed(() => {
    const usableCategoryIds = props.usableCategoryIdsByGroup[form.group_id] ?? [];

    return (props.categories ?? []).filter((category) => usableCategoryIds.includes(category.id));
});

const availableCategoryEntries = computed(() => flattenCategoryTree(availableCategories.value));

watch(
    () => form.group_id,
    () => {
        if (form.category_id !== null && !availableCategories.value.some((category) => category.id === form.category_id)) {
            form.category_id = null;
        }
    },
);

const isFetchingLocation = ref(false);

const locationInput = computed({
    get: () => form.location ?? '',
    set: (value: string) => {
        form.location = value;
    },
});

const locationError = ref<string | null>(null);

async function fetchLocation() {
    isFetchingLocation.value = true;
    locationError.value = null;

    try {
        const response = await axios.get(route('api.map.reverse-search'), {
            params: { lat: form.coordinate.lat, lng: form.coordinate.lng },
        });

        if (response.data.location) {
            form.location = response.data.location;
        } else {
            locationError.value = 'Zu dieser Position ist keine Adresse bekannt. Bitte beschreibe den Ort selbst.';
        }
    } catch {
        // The pin is already set, so the point stays usable. Only the
        // convenience of a prefilled address is lost.
        locationError.value = 'Die Adresse konnte nicht geladen werden. Bitte beschreibe den Ort selbst.';
    } finally {
        isFetchingLocation.value = false;
    }
}

watch(
    () => form.coordinate,
    () => {
        if (!form.location) {
            fetchLocation();
        }
    },
    { deep: true },
);

/**
 * Uploads are sent as multipart form data, which drops empty lists. They are sent as empty values instead, so
 * removing all images or unchecking all boxes still clears the field. PHP only parses multipart bodies of POST
 * requests, so updates with uploads are sent as POST with the method spoofed.
 */
function submit() {
    const hasUploads = Object.values(form.field_values).some((value) => Array.isArray(value) && value.some((entry) => entry instanceof File));
    const spoofsPut = isEditing && hasUploads;

    form.transform(({ fields: _fields, former_fields: _formerFields, field_values, ...data }) => ({
        ...data,
        ...(spoofsPut ? { _method: 'put' } : {}),
        field_values: Object.fromEntries(
            Object.entries(field_values).map(([id, value]) => [id, Array.isArray(value) && value.length === 0 ? '' : value]),
        ),
    }));

    if (!isEditing) {
        form.post(route('mappoints.store'));
    } else if (spoofsPut) {
        form.post(route('mappoints.update', props.mapPoint!.id));
    } else {
        form.put(route('mappoints.update', props.mapPoint!.id));
    }
}

const errors: Record<string, string> = form.errors;
</script>

<template>
    <div class="mx-auto w-full max-w-3xl">
        <PageHeader :title="isEditing ? 'Kartenpunkt bearbeiten' : 'Neuen Kartenpunkt erstellen'" />

        <Card>
            <form @submit.prevent="submit">
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="title">Titel</Label>
                        <Input id="title" v-model="form.title" required />
                        <p v-if="errors.title" class="text-sm text-red-500">{{ errors.title }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Beschreibung</Label>
                        <Textarea id="description" v-model="form.description" rows="4" />
                        <p v-if="errors.description" class="text-sm text-red-500">{{ errors.description }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="coordinate">Position auf der Karte</Label>
                        <PinLocationMap v-model="form.coordinate" />
                        <p v-if="errors.coordinate" class="text-sm text-red-500">{{ errors.coordinate }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="location">Ort</Label>
                        <div class="flex gap-2">
                            <Input id="location" v-model="locationInput" placeholder="Adresse" />
                            <Button type="button" variant="outline" :disabled="isFetchingLocation" @click="fetchLocation">
                                {{ isFetchingLocation ? 'Lädt...' : 'Adresse laden' }}
                            </Button>
                        </div>
                        <p v-if="errors.location" class="text-sm text-red-500">{{ errors.location }}</p>
                        <p v-if="locationError" class="text-sm text-amber-600">{{ locationError }}</p>
                        <p class="text-xs text-gray-500">
                            Wird beim Setzen der Position automatisch per Geocoding vorbefüllt, kann aber frei angepasst werden.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="group_id">Initiative</Label>
                        <Select id="group_id" v-model="form.group_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Wähle eine Initiative aus" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="group in groups" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.group_id" class="text-sm text-red-500">{{ errors.group_id }}</p>
                        <p class="text-xs text-gray-500">Der Punkt ist für diese Initiative und alle übergeordneten Initiativen sichtbar.</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="category">Kategorie</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Kategorie wählen (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="{ category, depth } in availableCategoryEntries"
                                    :key="category.id"
                                    :value="category.id"
                                    :style="{ paddingLeft: `${0.5 + depth * 1.25}rem` }"
                                >
                                    <div class="flex items-center gap-2">
                                        <div v-if="category.marker_image_path" class="h-4 w-4 flex-shrink-0">
                                            <img :src="category.marker_image_path" :alt="category.name" class="h-full w-full object-contain" />
                                        </div>
                                        <span>{{ category.name }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.category_id" class="text-sm text-red-500">{{ errors.category_id }}</p>
                    </div>

                    <div v-if="categoryFields.length > 0" class="space-y-4 rounded-md border p-4" data-test="category-fields">
                        <div>
                            <h3 class="text-sm font-semibold">Zusatzfelder der Kategorie</h3>
                            <p class="text-xs text-gray-500">
                                Alle Angaben sind freiwillig. Felder mit Schloss sind intern und erscheinen nicht auf der öffentlichen Karte.
                            </p>
                        </div>
                        <div v-for="field in categoryFields" :key="field.id" class="space-y-2">
                            <Label :for="`field_${field.id}`" class="flex items-center gap-1">
                                {{ field.label }}
                                <Lock v-if="!publicFieldIds.includes(field.id)" class="h-3 w-3 text-muted-foreground" aria-label="intern" />
                            </Label>
                            <p v-if="field.help_text" class="text-xs text-muted-foreground">{{ field.help_text }}</p>
                            <MapPointImageInput
                                v-if="field.type === 'image'"
                                :model-value="imageEntries(field.id)"
                                @update:model-value="(entries) => (form.field_values[field.id] = entries)"
                                :stored-images="storedImages(field.id)"
                                :max-images="field.max_images"
                                :has-error="!!fieldError(field.id)"
                            />
                            <FormFieldInputRenderer v-else v-model="form.field_values[field.id]" :field="field" :has-error="!!fieldError(field.id)" />
                            <p v-if="fieldError(field.id)" class="text-sm text-red-500">{{ fieldError(field.id) }}</p>
                        </div>
                    </div>

                    <div v-if="isEditing && props.mapPoint!.former_fields.length > 0" class="space-y-2 rounded-md border border-dashed p-4">
                        <div>
                            <h3 class="text-sm font-semibold">Frühere Angaben</h3>
                            <p class="text-xs text-gray-500">
                                Diese Felder gehören nicht mehr zur Kategorie des Punkts. Die Werte bleiben erhalten und sind nur hier sichtbar.
                            </p>
                        </div>
                        <MapPointFieldList :fields="props.mapPoint!.former_fields" />
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="published" v-model="form.published" />
                        <Label for="published">Veröffentlicht</Label>
                        <p v-if="errors.published" class="text-sm text-red-500">{{ errors.published }}</p>
                    </div>

                    <div v-if="isEditing">
                        <p class="text-sm text-gray-500">Typ: {{ props.mapPoint!.userReadablePointableType }}</p>
                    </div>
                </CardContent>

                <CardFooter class="flex justify-between">
                    <div></div>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Kartenpunkt aktualisieren' : 'Kartenpunkt erstellen' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
