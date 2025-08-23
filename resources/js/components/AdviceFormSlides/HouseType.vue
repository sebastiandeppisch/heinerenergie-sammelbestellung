<script setup lang="ts">
import DxButtonGroup from 'devextreme-vue/button-group';
import DxTextArea from 'devextreme-vue/text-area';

import { computed } from 'vue';

type HouseTypeAdvice = Pick<App.Models.Advice, 'place_notes' | 'house_type' | 'landlord_exists'>;

interface Props {
    modelValue: HouseTypeAdvice;
}

const houseTypes = [
    {
        id: 0,
        text: 'Einfamilienhaus',
        icon: 'fa fa-home',
    },
    {
        id: 1,
        text: 'Mehrfamilienhaus',
        icon: 'fa fa-building',
    },
    {
        id: 2,
        text: 'Sonstiges',
        icon: 'fa fa-sun',
    },
];

const props = defineProps<Props>();
const emit = defineEmits(['allowForward']);

function checkForm() {
    if (advice.value.place_notes === '' || advice.value.house_type === null) {
        //TODO
        emit('allowForward', false);
    } else {
        emit('allowForward', true);
    }
}

function houseTypeChanged(e: { addedItems: Array<{ id: number }> }) {
    console.log(e.addedItems[0].id);
    advice.value.house_type = e.addedItems[0].id;
}

function handleLandlordChange(e: { addedItems: Array<{ id: number }> }) {
    advice.value.landlord_exists = e.addedItems[0].id === 0;
}

const advice = computed<HouseTypeAdvice>({
    get() {
        return props.modelValue;
    },
    set(value) {},
});
</script>

<template>
    <div style="display: flex; flex-direction: column; height: 100%">
        <div style="flex: 1">
            <div style="display: flex; flex-direction: column; height: 100%">
                <div style="flex: 1">
                    <span style="font-size: 1.2em">In was für einem Haus möchtest Du Dein Steckersolargerät installieren?</span>
                    <DxButtonGroup :items="houseTypes" key-expr="id" width="100%" v-model="advice.house_type" @selection-changed="houseTypeChanged" />
                </div>
                <div style="flex: 1; margin-top: 32px">
                    <div v-show="advice.house_type !== null">
                        <span style="font-size: 1.2em">Musst Du bauliche Veränderungen mit einer WEG oder Vermieter*in absprechen?</span>
                        <DxButtonGroup
                            :items="[
                                { id: 0, text: 'Ja ' },
                                { id: 1, text: 'Nein' },
                            ]"
                            key-expr="id"
                            width="100%"
                            @selection-changed="handleLandlordChange"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div style="flex: 1; margin-top: 32px">
            <div v-show="advice.landlord_exists !== null">
                <span style="font-size: 1.2em">Wo möchtest Du Dein Steckersolargerät installieren?</span>
                <DxTextArea
                    v-model="advice.place_notes"
                    placeholder="Garage, Dach, Gartenhaus, ..."
                    height="70px"
                    @change="checkForm"
                    @key-up="checkForm"
                    value-change-event="keyup"
                />
            </div>
        </div>
    </div>
</template>
