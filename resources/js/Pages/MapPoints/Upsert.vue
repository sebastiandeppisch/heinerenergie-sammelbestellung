<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PinLocationMap from '@/components/PinLocationMap.vue';
import { Badge } from '@/shadcn/components/ui/badge';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Switch } from '@/shadcn/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/shadcn/components/ui/table';
import { Textarea } from '@/shadcn/components/ui/textarea';
import type { CustomPageProps } from '@/types/pageProps';
import { router, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPoint?: App.Data.MapPointData;
    categories?: Array<App.Data.MapPointCategoryData>;
    groups: Array<App.Data.GroupBaseData>;
    usableCategoryIdsByGroup: Record<string, Array<string>>;
    contactRequests?: Array<App.Data.MapPointContactRequestData>;
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
    contact_email: null,
    contact_enabled: false,
    contact_email_verified_at: null,
};

const form = useForm<App.Data.MapPointData>(props.mapPoint || defaultMapPoint);

/** Only categories of the selected initiative and its parent initiatives can be assigned. */
const availableCategories = computed(() => {
    const usableCategoryIds = props.usableCategoryIdsByGroup[form.group_id] ?? [];

    return (props.categories ?? []).filter((category) => usableCategoryIds.includes(category.id));
});

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

const contactEmailInput = computed({
    get: () => form.contact_email ?? '',
    set: (value: string) => {
        form.contact_email = value === '' ? null : value;
    },
});

const contactEmailSaved = computed(() => props.mapPoint?.contact_email !== null && props.mapPoint?.contact_email === form.contact_email);

function requestContactVerification() {
    router.post(route('mappoints.contact-verification', props.mapPoint!.id), {}, { preserveScroll: true });
}

const contactRequestStatusLabels: Record<App.Data.MapPointContactRequestData['status'], string> = {
    forwarded: 'Weitergeleitet',
    confirmed: 'Bestätigt, nicht weitergeleitet',
    expired: 'Nicht bestätigt (abgelaufen)',
    pending: 'Wartet auf Bestätigung',
};

function formatDate(date: string) {
    return new Date(date).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' });
}

function submit() {
    if (isEditing) {
        form.put(route('mappoints.update', props.mapPoint!.id));
    } else {
        form.post(route('mappoints.store'));
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
                                <SelectItem v-for="category in availableCategories" :key="category.id" :value="category.id">
                                    <div class="flex items-center gap-2">
                                        <div v-if="category.image_path" class="h-4 w-4 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                                            <img :src="category.image_path" :alt="category.name" class="h-full w-full object-cover" />
                                        </div>
                                        <span>{{ category.name }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.category_id" class="text-sm text-red-500">{{ errors.category_id }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="published" v-model="form.published" />
                        <Label for="published">Veröffentlicht</Label>
                        <p v-if="errors.published" class="text-sm text-red-500">{{ errors.published }}</p>
                    </div>

                    <div v-if="isEditing">
                        <p class="text-sm text-gray-500">Typ: {{ props.mapPoint!.userReadablePointableType }}</p>
                    </div>

                    <div class="space-y-4 rounded-lg border p-4">
                        <h3 class="font-semibold">Kontakt über die Karte</h3>

                        <div class="space-y-2">
                            <Label for="contact_email">Kontaktadresse</Label>
                            <Input id="contact_email" v-model="contactEmailInput" type="email" placeholder="name@example.org" />
                            <p v-if="errors.contact_email" class="text-sm text-red-500">{{ errors.contact_email }}</p>
                            <p class="text-xs text-gray-500">
                                Wird nie öffentlich angezeigt. Anfragen von der Karte werden an diese Adresse weitergeleitet. Eine hier eingetragene
                                Adresse gilt als bestätigt.
                            </p>
                        </div>

                        <div v-if="contactEmailSaved" class="flex flex-wrap items-center gap-2">
                            <Badge v-if="mapPoint!.contact_email_verified_at" data-test="contact-email-verified">Adresse bestätigt</Badge>
                            <Badge v-else variant="secondary" data-test="contact-email-unverified">Adresse nicht bestätigt</Badge>
                            <Button type="button" variant="outline" size="sm" @click="requestContactVerification">Bestätigung anfordern</Button>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Switch id="contact_enabled" v-model="form.contact_enabled" />
                            <Label for="contact_enabled">Kontakt über die Karte erlauben</Label>
                        </div>
                        <p class="text-xs text-gray-500">
                            Der Button „Kontakt aufnehmen“ erscheint nur, wenn der Punkt veröffentlicht ist, Kontakt erlaubt ist und die Adresse
                            bestätigt wurde.
                        </p>
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

        <Card v-if="isEditing" class="mt-6">
            <CardHeader>
                <CardTitle>Kontaktanfragen</CardTitle>
            </CardHeader>
            <CardContent>
                <p v-if="!contactRequests || contactRequests.length === 0" class="text-sm text-gray-500">Noch keine Kontaktanfragen.</p>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Datum</TableHead>
                            <TableHead>Absender:in</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Nachricht</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="contactRequest in contactRequests" :key="contactRequest.id">
                            <TableCell class="whitespace-nowrap">{{ formatDate(contactRequest.created_at) }}</TableCell>
                            <TableCell>
                                <div>{{ contactRequest.sender_name }}</div>
                                <div class="text-xs text-gray-500">{{ contactRequest.sender_email }}</div>
                            </TableCell>
                            <TableCell>{{ contactRequestStatusLabels[contactRequest.status] }}</TableCell>
                            <TableCell class="max-w-md whitespace-pre-wrap">{{ contactRequest.message }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <p class="mt-4 text-xs text-gray-500">Anfragen werden nach Ablauf der Aufbewahrungsfrist automatisch gelöscht.</p>
            </CardContent>
        </Card>
    </div>
</template>
