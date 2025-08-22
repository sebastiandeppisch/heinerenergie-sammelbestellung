<script setup lang="ts">
import DxButtonGroup from 'devextreme-vue/button-group';
import DxTextBox from 'devextreme-vue/text-box';

import { computed, ref } from 'vue';

import { DxRequiredRule, DxStringLengthRule, DxValidator } from 'devextreme-vue/validator';

type PlaceAdvice = Omit<Pick<App.Models.Advice, 'street' | 'streetNumber' | 'zip' | 'city' | 'type'>, 'type' | 'zip'> & {
    type: number | null;
    zip: number | null;
};

interface Props {
    modelValue: PlaceAdvice & Record<string, any>;
}

const props = defineProps<Props>();
const emit = defineEmits(['allowForward']);

function checkForm() {
    if (
        advice.value.street === '' ||
        advice.value.streetNumber === '' ||
        advice.value.zip === null ||
        advice.value.zip.toString() === '' ||
        advice.value.city === ''
    ) {
        emit('allowForward', false);
    } else {
        emit('allowForward', true);
    }
}

const advice = computed<PlaceAdvice>({
    get() {
        return props.modelValue;
    },
    set(value) {},
});

const adviceOrDirectOrder = [
    {
        id: 0,
        text: 'Beratung',
        icon: 'fa fa-handshake',
    },
];

const phoneOrHome = [
    {
        id: 1,
        text: 'Telefon / per E-Mail',
        icon: 'fa fa-phone',
    },
    {
        id: 0,
        text: 'Zuhause',
        icon: 'fa fa-home',
    },
];

const buttonsValid = ref(false);
const adviceOrDirectOrderIsSet = ref(true);

function phoneOrHomeChanged(e: any) {
    advice.value.type = e.addedItems[0].id;
}

const addressText = computed(() => {
    if (advice.value.type === 0) {
        return 'An welcher Adresse möchtest Du beraten werden?';
    } else if (advice.value.type === 1) {
        return 'An welcher Adresse möchtest Du Dein Steckersolargerät installieren?';
    } else if (advice.value.type === 2) {
        return 'Gib bitte Deine Adresse an, die wir an den Lieferanten weitergeben dürfen';
    }
});

function adviceTypeChanged(e: any) {
    //advice.value.adviceType = e.addedItems[0].id
    //checkForm()
}
</script>

<template>
    <div style="display: flex; flex-direction: column; height: 100%">
        <span style="font-size: 1.2em">Möchtest Du telefonisch oder vor Ort beraten werden?</span>
        <DxButtonGroup :items="phoneOrHome" key-expr="id" width="100%" @selection-changed="phoneOrHomeChanged" style="margin-top: 16px" />
        <div style="display: block; height: 42px" v-if="advice.type == 2"></div>
        <div v-if="advice.type !== null" style="margin-top: 16px">
            <span style="font-size: 1.2em">{{ addressText }}</span>
            <div style="display: flex; flex-direction: row">
                <DxTextBox
                    placeholder="Luisenstraße"
                    width="100%"
                    :element-attr="{ style: 'margin-right: 32px;flex:1' }"
                    label="Straße"
                    v-model="advice.street"
                    @change="checkForm"
                    @key-up="checkForm"
                    value-change-event="keyup"
                >
                    <DxValidator>
                        <DxRequiredRule message="Gib bitte Deine Straße an" />
                    </DxValidator>
                </DxTextBox>
                <DxTextBox
                    placeholder="42a"
                    width="100px"
                    label="Nr."
                    v-model="advice.streetNumber"
                    @change="checkForm"
                    @key-up="checkForm"
                    value-change-event="keyup"
                >
                    <DxValidator>
                        <DxRequiredRule message="Gib bitte Deine Hausnummer an" />
                    </DxValidator>
                </DxTextBox>
            </div>
            <div style="display: flex; margin-top: 32px">
                <DxTextBox
                    placeholder="64283"
                    width="150px"
                    label="Plz"
                    v-model="advice.zip"
                    @change="checkForm"
                    @key-up="checkForm"
                    value-change-event="keyup"
                    mask-invalid-message="Gib bitte eine 5 stellige Postleitzahl an"
                >
                    <DxValidator>
                        <DxRequiredRule message="Gib bitte Deine Postleitzahl an" />
                        <DxStringLengthRule message="Gib bitte eine 5 stellige Postleitzahl an" :min="5" :max="5" />
                    </DxValidator>
                </DxTextBox>
                <DxTextBox
                    placeholder="Darmstadt"
                    :element-attr="{ style: 'margin-left: 32px;flex:1' }"
                    label="Ort"
                    v-model="advice.city"
                    @change="checkForm"
                    @key-up="checkForm"
                    value-change-event="keyup"
                >
                    <DxValidator>
                        <DxRequiredRule message="Gib bitte Deine Stadt an" />
                    </DxValidator>
                </DxTextBox>
            </div>
        </div>
    </div>
</template>
