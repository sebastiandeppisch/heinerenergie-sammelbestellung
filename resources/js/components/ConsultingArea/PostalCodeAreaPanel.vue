<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/shadcn/components/ui/tags-input';
import axios, { AxiosError } from 'axios';
import { Download, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    group: App.Data.GroupData;
    hasArea: boolean;
}>();

const emit = defineEmits<{
    loaded: [polygon: App.ValueObjects.Polygon];
}>();

const postalCodes = defineModel<string[]>('postalCodes', { default: () => [] });
const loading = ref(false);
const error = ref<string | null>(null);

const canLoad = computed(() => postalCodes.value.length > 0 && !loading.value);

// The area was built from these postal codes and may have been reshaped by hand
// since, so reloading it is a deliberate step.
const builtFromPostalCodes = computed(() => props.hasArea && postalCodes.value.length > 0);

async function loadArea() {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.post<{ polygon: App.ValueObjects.Polygon }>(route('api.groups.postal-code-area', props.group.id), {
            postal_codes: postalCodes.value,
        });

        emit('loaded', data.polygon);
    } catch (exception) {
        error.value = messageOf(exception as AxiosError<{ message?: string; errors?: Record<string, string[]> }>);
    } finally {
        loading.value = false;
    }
}

function messageOf(exception: AxiosError<{ message?: string; errors?: Record<string, string[]> }>): string {
    const data = exception.response?.data;

    if (data?.errors) {
        return Object.values(data.errors).flat().join(' ');
    }

    return data?.message ?? 'Das Gebiet konnte nicht geladen werden. Bitte versuche es später erneut.';
}
</script>

<template>
    <div class="space-y-3">
        <p v-if="builtFromPostalCodes" class="text-sm text-gray-600">
            Dieses Beratungsgebiet beruht auf diesen Postleitzahlen. Ergänze oder entferne eine und lade es neu.
        </p>
        <p v-else class="text-sm text-gray-600">
            Lade die Grenzen aus OpenStreetMap als Grundlage für dein Beratungsgebiet. Mehrere Postleitzahlen müssen aneinandergrenzen und werden zu
            einem Gebiet zusammengefasst. Anschließend kannst du das Gebiet in der Karte weiter bearbeiten.
        </p>

        <div class="flex flex-wrap items-start gap-2">
            <TagsInput v-model="postalCodes" class="min-w-64 flex-1" :add-on-paste="true" delimiter=",">
                <TagsInputItem v-for="postalCode in postalCodes" :key="postalCode" :value="postalCode">
                    <TagsInputItemText />
                    <TagsInputItemDelete />
                </TagsInputItem>
                <TagsInputInput placeholder="PLZ eingeben und Enter drücken..." />
            </TagsInput>

            <Button variant="secondary" :disabled="!canLoad" @click="loadArea">
                <Download class="h-4 w-4" />
                {{ loading ? 'Lädt...' : 'Gebiet laden' }}
            </Button>
        </div>

        <p v-if="props.hasArea" class="text-sm text-gray-600">Das bestehende Beratungsgebiet wird dabei ersetzt, auch von Hand gesetzte Eckpunkte.</p>

        <p v-if="error" class="flex items-start gap-2 text-sm text-destructive">
            <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{{ error }}</span>
        </p>
    </div>
</template>
