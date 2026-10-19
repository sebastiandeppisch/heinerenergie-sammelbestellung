<script setup lang="ts">
import { Checkbox } from '@/shadcn/components/ui/checkbox';
import { Label } from '@/shadcn/components/ui/label';
import { consentLabels, consentText } from '@/utils/mapPointConsent';
import { computed } from 'vue';

const props = defineProps<{
    config: App.Data.MapPointConsentConfigData;
    values: Record<string, unknown>;
    disabled?: boolean;
}>();

const show = defineModel<boolean>('show', { required: true });
const details = defineModel<boolean>('details', { required: true });

const labels = computed(() => consentLabels(props.config, props.values));
const text = computed(() => consentText(props.config, props.values));

function updateShow(checked: boolean | 'indeterminate') {
    show.value = checked === true;

    if (!show.value) {
        details.value = false;
    }
}
</script>

<template>
    <fieldset class="space-y-3 rounded-md border p-4" data-test="map-point-consent">
        <legend class="px-1 text-sm font-medium">Veröffentlichung auf der Karte</legend>

        <div class="flex items-start gap-2">
            <Checkbox id="map-point-consent-show" :model-value="show" :disabled="disabled" @update:model-value="updateShow" />
            <Label for="map-point-consent-show" class="text-sm font-normal">Meinen Eintrag auf der Karte zeigen</Label>
        </div>

        <div v-if="show" class="ml-6 flex items-start gap-2">
            <Checkbox
                id="map-point-consent-details"
                :model-value="details"
                :disabled="disabled"
                @update:model-value="(checked) => (details = checked === true)"
            />
            <Label for="map-point-consent-details" class="text-sm font-normal">zusätzlich diese Angaben zeigen: {{ labels.join(', ') }}</Label>
        </div>

        <p v-if="show" class="text-sm text-muted-foreground" data-test="map-point-consent-text">{{ text }}</p>
        <p v-else class="text-sm text-muted-foreground">Ohne dein Häkchen erscheint dein Eintrag nicht auf der öffentlichen Karte.</p>
    </fieldset>
</template>
