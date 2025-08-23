<script setup lang="ts">
import { computed } from 'vue';

import otherHelpTypeImage from '@/../img/heinerenergie-hochzeitsturm.svg';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import DxCheckBox from 'devextreme-vue/check-box';

type HelpTypeAdvice = Pick<
    App.Models.Advice,
    'help_type_place' | 'help_type_technical' | 'help_type_bureaucracy' | 'help_type_other' | 'first_name' | 'last_name'
>;

interface Props {
    modelValue: HelpTypeAdvice;
}

const props = defineProps<Props>();
const emit = defineEmits(['allowForward']);

function checkForm() {
    if (advice.value.help_type_place || advice.value.help_type_bureaucracy || advice.value.help_type_technical || advice.value.help_type_other) {
        emit('allowForward', true);
    } else {
        emit('allowForward', false);
    }
}

const advice = computed<HelpTypeAdvice>({
    get() {
        return props.modelValue;
    },
    set(value) {},
});
</script>

<template>
    <div style="display: flex; flex-direction: column; gap: 32px">
        <span style="font-size: 1.2em">Bei was benötigst Du Beratung, {{ advice.first_name }}?</span>

        <div style="display: flex; flex-direction: row">
            <div style="width: 45px">
                <font-awesome-icon icon="fa fa-house" style="font-size: 2em" />
            </div>
            <div>
                <DxCheckBox v-model="advice.help_type_place" text="Ort (Balkon, Garten, Terrasse, etc.)" @value-changed="checkForm" />
                <div style="margin-left: 22px; margin-top: 3px">
                    <a href="heinerenergie.de" target="_blank">Mehr Infos zur Aufstellung</a>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: row">
            <div style="width: 45px">
                <font-awesome-icon icon="fa fa-file-signature" style="font-size: 2em" />
            </div>
            <div>
                <DxCheckBox v-model="advice.help_type_bureaucracy" text="Bürokratie (Anmeldung, Förderung, etc.)" @value-changed="checkForm" />
                <div style="margin-left: 22px; margin-top: 3px">
                    <a href="heinerenergie.de" target="_blank">Mehr Infos zum Papierkram</a>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: row">
            <div style="width: 45px">
                <font-awesome-icon icon="fa fa-wrench" style="font-size: 2em" />
            </div>
            <div>
                <DxCheckBox v-model="advice.help_type_technical" text="Technisches (Anschluss, Befestigung, etc.)" @value-changed="checkForm" />
                <div style="margin-left: 22px; margin-top: 3px">
                    <a href="heinerenergie.de" target="_blank">Mehr Infos zur Technik</a>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: row">
            <div style="width: 45px">
                <!-- TODO fix path -->
                <img :src="otherHelpTypeImage" style="height: 2em" />
            </div>
            <div>
                <DxCheckBox v-model="advice.help_type_other" text="Sonstiges" @value-changed="checkForm" />
                <div style="margin-left: 22px; margin-top: 3px">
                    <a href="heinerenergie.de" target="_blank">Unsere Webseite</a>
                </div>
            </div>
        </div>
    </div>
</template>
