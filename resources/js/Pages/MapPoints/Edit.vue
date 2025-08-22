<script setup lang="ts">
import PinLocationMap from '@/components/PinLocationMap.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Switch } from '@/shadcn/components/ui/switch';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { route } from 'ziggy-js';

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
        <div class="mx-auto mb-4 max-w-2xl">
            <Button variant="outline" @click="router.visit(route('mappoints.index'))">
                <ArrowLeft />
                Zurück
            </Button>
        </div>

        <Card class="mx-auto max-w-2xl">
            <CardHeader>
                <CardTitle>Kartenpunkt bearbeiten</CardTitle>
            </CardHeader>
            <form @submit.prevent="submit">
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="title">Titel</Label>
                        <Input id="title" v-model="form.title" required />
                        <p v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Beschreibung</Label>
                        <Textarea id="description" v-model="form.description" rows="4" />
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Ort</Label>
                        <PinLocationMap v-model="form.coordinate" />
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.coordinate }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="published" v-model="form.published" />
                        <Label for="published">Veröffentlicht</Label>
                        <p v-if="form.errors.published" class="text-sm text-red-500">{{ form.errors.published }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Typ: {{ props.mapPoint.userReadablePointableType }}</p>
                    </div>
                </CardContent>

                <CardFooter class="flex justify-between">
                    <div></div>
                    <Button type="submit" :disabled="form.processing"> Update Map Point </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
