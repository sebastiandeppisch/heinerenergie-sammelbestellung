<script setup lang="ts">
import { isIframe, useAutoResizeIframeIfIsIframe } from '@/helpers';
import NoLayout from '@/layouts/NoLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import { Textarea } from '@/shadcn/components/ui/textarea';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: isIframe ? NoLayout : PublicLayout,
});

const props = defineProps<{
    mapEmbedId: string;
    mapPoint: { id: string; title: string };
    messageMaxLength: number;
}>();

useAutoResizeIframeIfIsIframe();

const form = useForm('post', route('map-contact.store', [props.mapEmbedId, props.mapPoint.id]), {
    name: '',
    email: '',
    message: '',
    website: '',
});
form.setValidationTimeout(300);

function submit() {
    form.submit();
}
</script>

<template>
    <div class="mx-auto w-full max-w-2xl space-y-4">
        <Link :href="route('map.public', mapEmbedId)" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:underline">
            <ArrowLeft class="h-4 w-4" />
            Zurück zur Karte
        </Link>

        <Card>
            <CardHeader>
                <CardTitle>Kontakt aufnehmen</CardTitle>
                <CardDescription>
                    Deine Nachricht zum Eintrag „{{ mapPoint.title }}“ leiten wir an die Verantwortlichen weiter, sobald du deine E-Mail-Adresse
                    bestätigt hast. Deine Adresse sieht die Person erst, wenn sie dir antwortet.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="contact-name">Name</Label>
                        <Input id="contact-name" v-model="form.name" name="name" autocomplete="name" required @change="form.validate('name')" />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="contact-email">E-Mail-Adresse</Label>
                        <Input
                            id="contact-email"
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            @change="form.validate('email')"
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="contact-message">Nachricht</Label>
                        <Textarea
                            id="contact-message"
                            v-model="form.message"
                            name="message"
                            rows="6"
                            :maxlength="messageMaxLength"
                            required
                            @change="form.validate('message')"
                        />
                        <p class="text-xs text-muted-foreground">{{ form.message.length }} / {{ messageMaxLength }} Zeichen</p>
                        <p v-if="form.errors.message" class="text-sm text-red-500">{{ form.errors.message }}</p>
                    </div>

                    <div class="hidden" aria-hidden="true">
                        <Label for="contact-website">Website</Label>
                        <Input id="contact-website" v-model="form.website" name="website" tabindex="-1" autocomplete="off" />
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Wird gesendet...' : 'Nachricht senden' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
