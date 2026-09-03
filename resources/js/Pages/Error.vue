<script setup lang="ts">
import AccessDenied from '@/components/AccessDenied.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/components/ui/card';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{
    status: number;
    message: string;
    intendedUrl?: string | null;
}>();

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit(route('dashboard'));
}
</script>

<template>
    <div class="flex flex-1 items-center justify-center p-6">
        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle>Keine Berechtigung</CardTitle>
                <CardDescription>Diese Seite ist mit Deiner aktuellen Rolle nicht verfügbar.</CardDescription>
            </CardHeader>
            <CardContent>
                <AccessDenied :message="message" :intended-url="intendedUrl" @back="goBack" />
            </CardContent>
        </Card>
    </div>
</template>
