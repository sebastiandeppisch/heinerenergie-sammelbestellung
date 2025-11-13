<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import axios from 'axios';
import { DxLoadIndicator } from 'devextreme-vue/load-indicator';
import DxTextArea from 'devextreme-vue/text-area';
import notify from 'devextreme/ui/notify';
import { Send } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type BaseAdvice = Pick<
    App.Models.Advice,
    | 'first_name'
    | 'last_name'
    | 'email'
    | 'phone'
    | 'street'
    | 'street_number'
    | 'zip'
    | 'city'
    | 'type'
    | 'help_type_place'
    | 'help_type_technical'
    | 'help_type_bureaucracy'
    | 'help_type_other'
    | 'house_type'
    | 'landlord_exists'
    | 'place_notes'
    | 'commentary'
>;

type SubmitAdvice = Omit<BaseAdvice, 'type' | 'zip'> & {
    type: number | null;
    zip: number | null;
};

interface Props {
    modelValue: SubmitAdvice;
}

const props = defineProps<Props>();
const emit = defineEmits(['submit']);

const loadIndicatorVisible = ref(false);

const loading = ref(false);

function submit() {
    loadIndicatorVisible.value = true;
    loading.value = true;
    const startTime = new Date().getTime();
    axios
        .post(route('api.newadvice'), advice.value)
        .then((response) => {
            const endTime = new Date().getTime();
            const timeDiff = endTime - startTime;
            setTimeout(
                () => {
                    emit('submit');
                },
                Math.max(0, 1000 - timeDiff),
            );
            console.log(response);
        })
        .catch((error) => {
            console.log(error);
            if ('response' in error && 'data' in error.response && 'message' in error.response.data) {
                notify(error.response.data.message, 'error');
            } else {
                notify('error', 'error');
            }
        })
        .finally(() => {
            const endTime = new Date().getTime();
            const timeDiff = endTime - startTime;
            setTimeout(
                () => {
                    loadIndicatorVisible.value = false;
                    loading.value = false;
                },
                Math.max(0, 1000 - timeDiff),
            );
        });
}

const advice = computed<Props['modelValue']>({
    get() {
        return props.modelValue;
    },
    set(value) {},
});
</script>

<template>
    <div style="display: flex; flex-direction: column; height: 100%">
        <span style="font-size: 1.2em">Hast Du sonst noch Fragen oder möchtest ein Kommentar hinzufügen?</span>
        <DxTextArea v-model="advice.commentary" placeholder="Ich habe noch eine Frage zur Balkonhalterung..." height="calc(100% - 100px)" />
        <div style="height: 32px; display: block"></div>
        <Button variant="default" @click="submit" :disabled="loading" class="h-12">
            <Send class="h-4 w-4" />
            Beratungsanfrage abschicken
        </Button>
        <div style="align-items: center; display: flex; flex-direction: column; padding-top: 8px">
            <DxLoadIndicator :visible="loadIndicatorVisible" height="32px" width="32px" />
            <div style="display: block; height: 32px" v-if="!loadIndicatorVisible"></div>
        </div>
        <div style="height: 16px; display: block"></div>
    </div>
</template>
