<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/shadcn/components/ui/button';
import Card from '@/shadcn/components/ui/card/Card.vue';
import CardContent from '@/shadcn/components/ui/card/CardContent.vue';
import { Link, router } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    token: string;
    proof: string | null;
    successMessage: string;
    /** Details the point has now that its consent does not cover yet, e.g. fields of a newly chosen characteristic. */
    newDetails: { labels: string[]; details_text: string } | null;
}>();

const processing = ref(false);
const params = computed(() => (props.proof ? { token: props.token, nachweis: props.proof } : { token: props.token }));

function showToo() {
    router.put(
        route('map-point.manage.consent', params.value),
        { show: true, details: true },
        { onStart: () => (processing.value = true), onFinish: () => (processing.value = false) },
    );
}
</script>

<template>
    <div class="flex flex-col items-center justify-center">
        <Card class="w-full max-w-200">
            <CardContent class="flex flex-col items-center gap-6 text-center">
                <div class="font-semibold">{{ successMessage }}</div>
                <CircleCheck :size="96" style="color: #00a651" />

                <div v-if="newDetails" class="w-full rounded-md border p-4 text-left" data-test="follow-up-new-details">
                    <div class="font-semibold">Diese neuen Angaben sind noch nicht öffentlich: {{ newDetails.labels.join(', ') }}</div>
                    <p class="mt-2 text-sm text-muted-foreground">{{ newDetails.details_text }}</p>
                    <Button class="mt-3" :disabled="processing" data-test="follow-up-show-too" @click="showToo">Auch zeigen</Button>
                </div>

                <Link :href="route('map-point.manage.show', params)" class="text-sm underline">Zurück zu deinem Eintrag</Link>
            </CardContent>
        </Card>
    </div>
</template>
