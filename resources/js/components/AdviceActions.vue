<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import DxButton from 'devextreme-vue/button';
import DxDropDownButton from 'devextreme-vue/drop-down-button';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import { user } from '../authHelper';
import AdviceTransfer from './AdviceTransfer.vue';
const props = defineProps<{
    advice: App.Data.DataProtectedAdviceData;
    advisor?: {
        first_name: string;
        email: string;
        lat: number | null;
        long: number | null;
    };
    transferableGroups: App.Data.GroupData[];
}>();

const navigationTypes = [
    { id: 'google', name: 'Google Maps' },
    { id: 'apple', name: 'Apple Maps' },
    { id: 'osm', name: 'Open Street Maps' },
];

function openNavigation(e: { itemData: { id: string } }) {
    const type = e.itemData.id;
    const address = props.advice.street + ' ' + props.advice.street_number + ', ' + props.advice.zip + ' ' + props.advice.city;

    switch (type) {
        case 'google':
            window.open('https://www.google.com/maps/dir/?api=1&destination=' + address + '&travelmode=bicycling', '_blank');
            break;
        case 'apple':
            window.open('https://maps.apple.com/?daddr=' + address + '&dirflg=w', '_blank');
            break;
        case 'osm':
            if (props.advisor?.lat && props.advisor?.long && props.advice.lat && props.advice.lng) {
                window.open(
                    'https://www.openstreetmap.org/directions?engine=graphhopper_bicycle&route=' +
                        props.advisor.lat +
                        '%2C' +
                        props.advisor.long +
                        '%3B' +
                        props.advice.lat +
                        '%2C' +
                        props.advice.lng,
                    '_blank',
                );
            }
            break;
    }
}

const mailLink = computed(() => {
    const body = 'Hallo ' + props.advice.first_name + ',%0D%0A%0D%0A' + 'TEXT' + '%0D%0A%0D%0A' + 'Gruß,%0D%0A' + props.advisor?.first_name;
    const subject = 'heiner*energie%20Beratung';

    return 'mailto:' + props.advice.email + '?subject=' + subject + '&body=' + body;
});

const phoneLink = computed(() => {
    return 'tel:' + props.advice.phone;
});

function unassignAdvice() {
    router.post(route('advices.unassign', props.advice.id));
}

const showUnassignButton = computed(() => {
    const userId = user.value.id;
    return props.advice.advisor_id === userId;
});
</script>

<template>
    <div style="display: flex; gap: 20px">
        <DxDropDownButton
            :items="navigationTypes"
            icon="map"
            text="Navigation öffnen"
            @item-click="openNavigation"
            display-expr="name"
            key-expr="id"
        />
        <a :href="phoneLink"><DxButton text="Anrufen" icon="tel" /></a>
        <a :href="mailLink"><DxButton text="E-Mail verfassen" icon="email" /></a>
        <DxButton v-if="showUnassignButton" text="Beratung freigeben" @click="unassignAdvice" hint="Beratung freigeben" />
        <AdviceTransfer :advice-id="advice.id" :transferable-groups="transferableGroups" />
    </div>
</template>
