<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shadcn/components/ui/select';
import type { CustomPageProps } from '@/types/pageProps';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    category?: App.Data.MapPointCategoryData;
    groups: Array<App.Data.GroupBaseData>;
}>();

const page = usePage<CustomPageProps>();

const isEditing = computed(() => !!props.category);
const fileInput = ref<HTMLInputElement>();

const form = useForm({
    name: props.category?.name || '',
    /** The initiative is only chosen on creation, new categories default to the current initiative. */
    group_id: props.category?.group_id ?? page.props.auth.currentGroup?.id ?? props.groups[0]?.id ?? null,
    image: null as File | null,
    _method: isEditing.value ? 'put' : 'post',
});

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
    <div class="container mx-auto py-8">
        <div class="mx-auto mb-4 max-w-2xl">
            <Button variant="outline" @click="router.visit(route('mappoint-categories.index'))">
                <ArrowLeft />
                Zurück
            </Button>
        </div>

        <Card class="mx-auto max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEditing ? 'Kategorie bearbeiten' : 'Neue Kategorie erstellen' }}</CardTitle>
            </CardHeader>
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
                        <Label>Kategorie-Bild (Pin für Karte)</Label>

                        <!-- Current Image Display -->
                        <div v-if="isEditing && category?.image_path" class="mb-4">
                            <p class="mb-2 text-sm text-gray-600">Aktuelles Bild:</p>
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                <img :src="category.image_path" :alt="category.name" class="h-full w-full object-cover" />
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div v-if="form.image && imagePreviewUrl" class="mb-4">
                            <p class="mb-2 text-sm text-gray-600">Neue Bildvorschau:</p>
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                <img :src="imagePreviewUrl" alt="Vorschau" class="h-full w-full object-cover" />
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
                </CardContent>

                <CardFooter class="flex justify-between">
                    <div></div>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Kategorie aktualisieren' : 'Kategorie erstellen' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
