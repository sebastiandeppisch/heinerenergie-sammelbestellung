<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { defineOptions, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import InputError from '@/components/InputError.vue';
import { Button } from '@/shadcn/components/ui/button';
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';

interface User {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    devUsers?: User[];
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function onSubmit() {
    form.post(route('login.store'));
}

const page = usePage();
onMounted(() => {
    const flash = page.props.flashMessages as Record<string, string> | undefined;
    if (flash?.success) {
        toast.success(flash.success);
    }
});

defineOptions({
    layout: MainPublic,
});
</script>

<template>
    <SingleCard title="Login" description="für Berater:innen">
        <form class="space-y-4" @submit.prevent="onSubmit">
            <div class="grid gap-2">
                <Label for="email">E-Mail</Label>
                <Input id="email" v-model="form.email" type="email" placeholder="E-Mail" autocomplete="email" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Passwort</Label>
                <Input id="password" v-model="form.password" type="password" placeholder="Passwort" autocomplete="current-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="remember" v-model:checked="form.remember" />
                <Label for="remember" class="cursor-pointer font-normal">Eingeloggt bleiben</Label>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <span v-if="form.processing">Einloggen...</span>
                <span v-else>Login</span>
            </Button>

            <div class="text-center text-sm">
                <Link href="/reset-password" class="text-muted-foreground hover:text-foreground">Passwort vergessen</Link>
            </div>
        </form>

        <div v-if="props.devUsers && props.devUsers.length > 0" class="mt-6 border-t pt-6">
            <h3 class="mb-4 text-lg font-medium text-gray-600">Direkter Login</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="user in props.devUsers" :key="user.id">
                    <Link
                        :href="`/dev-login/${user.id}`"
                        :title="user.email"
                        class="block w-full truncate rounded border border-gray-300 p-3 text-center text-sm transition-colors hover:bg-gray-100"
                    >
                        {{ user.name }}
                    </Link>
                </div>
            </div>
        </div>
    </SingleCard>
</template>
