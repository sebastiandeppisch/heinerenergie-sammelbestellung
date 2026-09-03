<script setup lang="ts">
import AccessDenied from '@/components/AccessDenied.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shadcn/components/ui/dialog';
import type { CustomPageProps } from '@/types/pageProps';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage<CustomPageProps>();

const open = ref(false);
/** Kept locally so the content does not vanish while the dialog animates out. */
const error = ref<NonNullable<CustomPageProps['authorizationError']> | null>(null);

watch(
    () => page.props.authorizationError,
    (newError) => {
        if (newError) {
            error.value = newError;
            open.value = true;

            return;
        }

        open.value = false;
    },
    { immediate: true },
);
</script>

<template>
    <Dialog v-model:open="open">
        <!-- Do not put focus on a role switch button, so a stray Enter cannot change the role. -->
        <DialogContent v-if="error" @open-auto-focus="(event: Event) => event.preventDefault()">
            <DialogHeader>
                <DialogTitle>Keine Berechtigung</DialogTitle>
                <DialogDescription>Diese Aktion ist mit Deiner aktuellen Rolle nicht möglich.</DialogDescription>
            </DialogHeader>
            <AccessDenied :message="error.message" :intended-url="error.intendedUrl" @back="open = false" @switch-role="open = false" />
        </DialogContent>
    </Dialog>
</template>
