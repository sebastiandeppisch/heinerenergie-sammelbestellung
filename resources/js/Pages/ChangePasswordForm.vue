<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { defineOptions } from 'vue';
import { route } from 'ziggy-js';
import InputError from '@/components/InputError.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function onSubmit() {
    form.post(route('change-password.store'));
}

defineOptions({
    layout: MainPublic,
});
</script>

<template>
    <SingleCard title="Neues Passwort setzen">
        <form class="space-y-4" @submit.prevent="onSubmit">
            <div class="grid gap-2">
                <Label for="password">Passwort</Label>
                <Input id="password" v-model="form.password" type="password" placeholder="Passwort" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Passwort bestätigen</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="Passwort bestätigen"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <span v-if="form.processing">Wird gespeichert...</span>
                <span v-else>Passwort setzen</span>
            </Button>
        </form>
    </SingleCard>
</template>
