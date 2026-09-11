<script setup lang="ts">
import PinLocationMap from '@/components/PinLocationMap.vue';
import { LaravelValidationError, notifyError } from '@/helpers';
import { Button } from '@/shadcn/components/ui/button';
import { router } from '@inertiajs/vue3';
import axios, { AxiosError } from 'axios';
import { Loader2, MapPin, TriangleAlert } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

type Coordinate = App.ValueObjects.Coordinate;

const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
}>();

/**
 * How long to wait before checking whether the lookup finished. The job runs
 * right after the response that created the advice, so it is usually done
 * within a second.
 */
const POLL_DELAY_MS = 2500;

const isPlacingPin = ref(false);
const isSaving = ref(false);
const pin = ref<Coordinate | null>(null);

let pollTimer: ReturnType<typeof setTimeout> | undefined;

onMounted(() => {
    if (props.advice.geocoding_status !== 'pending') {
        return;
    }

    pollTimer = setTimeout(() => {
        router.reload({ only: ['advice'] });
    }, POLL_DELAY_MS);
});

onBeforeUnmount(() => {
    clearTimeout(pollTimer);
});

async function savePin() {
    if (!pin.value) {
        return;
    }

    isSaving.value = true;

    try {
        await axios.put(route('api.advices.coordinate', props.advice.id), {
            lat: pin.value.lat,
            lng: pin.value.lng,
        });

        toast.success('Position gespeichert');
        isPlacingPin.value = false;
        router.reload({ only: ['advice'] });
    } catch (error) {
        notifyError(error as AxiosError<LaravelValidationError>);
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <div>
        <p v-if="advice.geocoding_status === 'pending'" class="flex items-center gap-2 text-sm text-gray-600">
            <Loader2 class="h-4 w-4 animate-spin" />
            <span>Die Position wird gerade aus der Adresse ermittelt.</span>
        </p>

        <div v-else-if="advice.geocoding_status === 'not_found' || advice.geocoding_status === 'failed'" class="space-y-2">
            <p class="flex items-start gap-2 text-sm text-amber-600">
                <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0" />
                <span v-if="advice.geocoding_status === 'not_found'">
                    Zu dieser Adresse ist bei OpenStreetMap keine Position bekannt. Setze sie bitte selbst.
                </span>
                <span v-else> Die Position konnte nicht ermittelt werden, der Adressdienst war nicht erreichbar. Du kannst sie selbst setzen. </span>
            </p>

            <Button v-if="!isPlacingPin" variant="outline" size="sm" @click="isPlacingPin = true">
                <MapPin class="mr-2 h-4 w-4" />
                Position selbst setzen
            </Button>

            <div v-else class="space-y-2">
                <PinLocationMap v-model="pin" />
                <div class="flex gap-2">
                    <Button size="sm" :disabled="!pin || isSaving" @click="savePin">
                        {{ isSaving ? 'Speichert...' : 'Position speichern' }}
                    </Button>
                    <Button variant="ghost" size="sm" :disabled="isSaving" @click="isPlacingPin = false">Abbrechen</Button>
                </div>
            </div>
        </div>
    </div>
</template>
