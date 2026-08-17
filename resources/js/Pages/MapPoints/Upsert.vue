<script setup lang="ts">
import PinLocationMap from '@/components/PinLocationMap.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import { Switch } from '@/shadcn/components/ui/switch';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    mapPoint?: App.Data.MapPointData;
    categories?: Array<App.Data.MapPointCategoryData>;
}>();

const isEditing = !!props.mapPoint;

const defaultMapPoint: App.Data.MapPointData = {
    id: '',
    title: '',
    description: '',
    coordinate: { lat: 0, lng: 0 },
    published: false,
    userReadablePointableType: '',
    created_at: null,
    category_id: null,
    location: null,
};

const form = useForm<App.Data.MapPointData>(props.mapPoint || defaultMapPoint);

const isFetchingLocation = ref(false);

const locationInput = computed({
    get: () => form.location ?? '',
    set: (value: string) => {
        form.location = value;
    },
});

async function fetchLocation() {
    isFetchingLocation.value = true;
    try {
        const response = await axios.get(route('api.map.reverse-search'), {
            params: { lat: form.coordinate.lat, lng: form.coordinate.lng },
        });
        form.location = response.data.location;
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
    <div class="container mx-auto py-8">
        <div class="mx-auto mb-4 max-w-2xl">
            <Button variant="outline" @click="router.visit(route('mappoints.index'))">
                <ArrowLeft />
                Zurück
            </Button>
        </div>

        <Card class="mx-auto max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEditing ? 'Kartenpunkt bearbeiten' : 'Neuen Kartenpunkt erstellen' }}</CardTitle>
            </CardHeader>
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
                        <p class="text-xs text-gray-500">
                            Wird beim Setzen der Position automatisch per Geocoding vorbefüllt, kann aber frei angepasst werden.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="category">Kategorie</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Kategorie wählen (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="category in props.categories" :key="category.id" :value="category.id">
                                    <div class="flex items-center gap-2">
                                        <div v-if="category.image_path" class="h-4 w-4 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                                            <img :src="category.image_path" :alt="category.name" class="h-full w-full object-cover" />
                                        </div>
                                        <span>{{ category.name }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
