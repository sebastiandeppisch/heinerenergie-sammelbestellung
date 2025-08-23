<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { defineOptions } from 'vue';
import { route } from 'ziggy-js';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';

// Import shadcn components
import InputError from '@/components/InputError.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
});

function onSubmit() {
    form.post(route('register.store'));
}

defineOptions({
    layout: MainPublic,
});
</script>

<template>
    <SingleCard
        title="Registierung"
        description="Herzlich Willkommen in Deiner neuen Ehrenamt-CRM Instanz. Als erstes musst Du einen neuen Administrator Zugang anlegen."
    >
        <form class="space-y-6" @submit.prevent="onSubmit">
            <div class="grid gap-2 space-y-4">
                <div class="grid gap-2">
                    <Label for="first_name">Vorname</Label>
                    <Input id="first_name" v-model="form.first_name" placeholder="Vorname" />
                    <InputError :message="form.errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">Nachname</Label>
                    <Input id="last_name" v-model="form.last_name" placeholder="Nachname" />
                    <InputError :message="form.errors.last_name" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">E-Mail Adresse</Label>
                    <Input id="email" v-model="form.email" type="email" placeholder="E-Mail Adresse" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Passwort</Label>
                    <Input id="password" v-model="form.password" type="password" placeholder="Passwort" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
                <Button type="submit" class="w-full" :disabled="form.processing">
                    <span v-if="form.processing">Administrator wird erstellt...</span>
                    <span v-else>Registrieren</span>
                </Button>
            </div>
        </form>
    </SingleCard>
</template>
