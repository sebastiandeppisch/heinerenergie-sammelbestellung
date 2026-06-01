<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { defineOptions } from 'vue';
import { route } from 'ziggy-js';
import InputError from '@/components/InputError.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';

const form = useForm({
    email: '',
});

function onSubmit() {
    form.post(route('forgot-password'));
}

defineOptions({
    layout: MainPublic,
});
</script>

<template>
    <SingleCard title="Neues Passwort" description="Trage hier Deine E-Mail Adresse ein, um ein neues Passwort zu erhalten">
        <form class="space-y-4" @submit.prevent="onSubmit">
            <div class="grid gap-2">
                <Label for="email">E-Mail</Label>
                <Input id="email" v-model="form.email" type="email" placeholder="E-Mail" autocomplete="email" />
                <InputError :message="form.errors.email" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <span v-if="form.processing">Wird gesendet...</span>
                <span v-else>Passwort zurücksetzen</span>
            </Button>

            <div class="text-center text-sm">
                <Link href="/login-form" class="text-muted-foreground hover:text-foreground">Zurück zum Login</Link>
            </div>
        </form>
    </SingleCard>
</template>
