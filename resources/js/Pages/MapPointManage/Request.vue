<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    /** Set when a link for one point is requested, it goes to the point's contact address. */
    mapPointId: string | null;
}>();

const form = useForm({ email: '' });

function submit() {
    if (props.mapPointId !== null) {
        form.post(route('map-point.manage.send-for-point', props.mapPointId));
    } else {
        form.post(route('map-point.manage.send-for-address'));
    }
}
</script>

<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent>
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <h1 class="text-xl font-semibold">Eintrag verwalten</h1>
                    <template v-if="mapPointId !== null">
                        <p class="text-sm text-muted-foreground">
                            Wenn du diesen Eintrag angelegt hast, schicken wir dir einen Link an die E-Mail-Adresse, die du dabei angegeben hast. Mit
                            dem Link kannst du ändern, was öffentlich angezeigt wird, deine Einwilligung widerrufen oder den Eintrag löschen.
                        </p>
                    </template>
                    <template v-else>
                        <p class="text-sm text-muted-foreground">
                            Gib die E-Mail-Adresse ein, die du bei deinem Eintrag angegeben hast. Wir schicken dir für jeden Eintrag einen Link, mit
                            dem du ihn verwalten kannst.
                        </p>
                        <div class="flex flex-col gap-2">
                            <Label for="manage-email">E-Mail-Adresse</Label>
                            <Input id="manage-email" v-model="form.email" type="email" required autocomplete="email" />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                    </template>
                    <div>
                        <Button type="submit" :disabled="form.processing">Link anfordern</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
