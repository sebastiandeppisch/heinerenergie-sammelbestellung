<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Switch } from '@/shadcn/components/ui/switch';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/shadcn/components/ui/card';
import { route } from 'ziggy-js';
import PinLocationMap from '@/components/PinLocationMap.vue';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
  mapPoint: App.Data.MapPointData;
}>();

const form = useForm<App.Data.MapPointData>(props.mapPoint);

function submit() {
  form.put(route('mappoints.update', props.mapPoint.id));
}


</script>

<template>


  <div class="container mx-auto py-8">

    <div class="max-w-2xl mx-auto mb-4">

        <Button
            variant="outline"
            @click="router.visit(route('mappoints.index'))"
        >
            <ArrowLeft/>
            Zurück
        </Button>
    </div>

    <Card class="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>Kartenpunkt bearbeiten</CardTitle>
      </CardHeader>
      <form @submit.prevent="submit">
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="title">Titel</Label>
            <Input id="title" v-model="form.title" required />
            <p v-if="form.errors.title" class="text-red-500 text-sm">{{ form.errors.title }}</p>
          </div>

          <div class="space-y-2">
            <Label for="description">Beschreibung</Label>
            <Textarea id="description" v-model="form.description" rows="4" />
            <p v-if="form.errors.description" class="text-red-500 text-sm">{{ form.errors.description }}</p>
          </div>

           <div class="space-y-2">
            <Label for="description">Ort</Label>
            <PinLocationMap v-model="form.coordinate"/>
            <p v-if="form.errors.description" class="text-red-500 text-sm">{{ form.errors.coordinate }}</p>
          </div>

          <div class="flex items-center space-x-2">
            <Switch id="published" v-model="form.published" />
            <Label for="published">Veröffentlicht</Label>
            <p v-if="form.errors.published" class="text-red-500 text-sm">{{ form.errors.published }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500">
              Typ: {{ props.mapPoint.userReadablePointableType }}
            </p>
          </div>
        </CardContent>

        <CardFooter class="flex justify-between">
          <div></div>
          <Button
            type="submit"
            :disabled="form.processing"
          >
            Update Map Point
          </Button>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
