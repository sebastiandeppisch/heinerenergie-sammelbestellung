<script setup lang="ts">
import { computed } from 'vue';

type SentAdvice = Pick<App.Models.Advice, 'firstName'>;

interface Props {
    modelValue: SentAdvice;
}

const props = defineProps<Props>();

const advice = computed<Props['modelValue']>({
    get() {
        return props.modelValue;
    },
    set(value) {},
});
</script>

<template>
    <div style="display: flex; align-items: center; height: 90%; flex-direction: column">
        <div style="font-size: 1.5em; width: 80%; margin-top: 32px">
            Vielen Dank für Deine Beratungsanfrage, {{ advice.firstName }}. Wir werden uns in Kürze bei Dir melden
            <font-awesome-icon icon="sun" style="color:" /> <br />
        </div>
        <div style="font-size: 1em; width: 80%; margin-top: 16px">
            Du erhältst eine Kopie Deiner Anfrage per E-Mail. Falls Du keine Bestätigungsmail erhältst, schau bitte in Deinem Spam-Ordner nach oder
            melde Dich nochmal bei uns.
        </div>
        <div style="margin-top: 32px">
            <font-awesome-icon icon="circle-check" size="6x" style="color: #00a651" />
        </div>
    </div>
</template>
