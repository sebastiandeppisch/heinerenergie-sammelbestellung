<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/shadcn/components/ui/card';
import { route } from 'ziggy-js';
import { ArrowLeft, Upload } from 'lucide-vue-next';

const props = defineProps<{
  category?: App.Data.CategoryData;
}>();

const isEditing = !!props.category;
const fileInput = ref<HTMLInputElement>();

const form = useForm({
  name: props.category?.name || '',
  image: null as File | null,
});

const imagePreviewUrl = computed(() => {
  if (form.image) {
    return URL.createObjectURL(form.image);
  }
  return null;
});

function submit() {
  if (isEditing) {
    form.post(route('categories.update', props.category!.id), {
      forceFormData: true,
      method: 'put'
    });
  } else {
    form.post(route('categories.store'), {
      forceFormData: true
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
    <div class="max-w-2xl mx-auto mb-4">
      <Button
        variant="outline"
        @click="router.visit(route('categories.index'))"
      >
        <ArrowLeft/>
        Zurück
      </Button>
    </div>

    <Card class="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>{{ isEditing ? 'Kategorie bearbeiten' : 'Neue Kategorie erstellen' }}</CardTitle>
      </CardHeader>
      <form @submit.prevent="submit">
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" required />
            <p v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label>Kategorie-Bild (Pin für Karte)</Label>
            
            <!-- Current Image Display -->
            <div v-if="isEditing && category?.image_path" class="mb-4">
              <p class="text-sm text-gray-600 mb-2">Aktuelles Bild:</p>
              <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                <img 
                  :src="`/storage/${category.image_path}`" 
                  :alt="category.name"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>

            <!-- Image Preview -->
            <div v-if="form.image && imagePreviewUrl" class="mb-4">
              <p class="text-sm text-gray-600 mb-2">Neue Bildvorschau:</p>
              <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                <img 
                  :src="imagePreviewUrl" 
                  alt="Vorschau"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>

            <!-- File Input -->
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              @change="handleFileSelect"
              class="hidden"
            />
            
            <Button
              type="button"
              variant="outline"
              @click="triggerFileInput"
              class="w-full"
            >
              <Upload class="h-4 w-4 mr-2"/>
              {{ form.image ? 'Anderes Bild wählen' : (isEditing && category?.image_path ? 'Bild ersetzen' : 'Bild hochladen') }}
            </Button>
            
            <p v-if="form.errors.image" class="text-red-500 text-sm">{{ form.errors.image }}</p>
            <p class="text-xs text-gray-500">
              Das Bild wird als Pin-Symbol auf der Karte verwendet. Empfohlen: Quadratisches Format, mindestens 32x32 Pixel.
            </p>
          </div>
        </CardContent>

        <CardFooter class="flex justify-between">
          <div></div>
          <Button
            type="submit"
            :disabled="form.processing"
          >
            {{ isEditing ? 'Kategorie aktualisieren' : 'Kategorie erstellen' }}
          </Button>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
