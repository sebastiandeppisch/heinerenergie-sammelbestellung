<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import DxDropDownButton from 'devextreme-vue/drop-down-button';
import { Mail, Phone, Unlock } from 'lucide-vue-next';
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

interface CustomPageProps extends PageProps {
    appName: string;
}

const page = usePage<CustomPageProps>();

const mailLink = computed(() => {
    const body = 'Hallo ' + props.advice.first_name + ',%0D%0A%0D%0A' + 'TEXT' + '%0D%0A%0D%0A' + 'Gruß,%0D%0A' + props.advisor?.first_name;
    const appName = page.props.appName || 'CRM-System';
    const subject = encodeURIComponent(appName + ' Beratung');

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
        <Button as="a" :href="phoneLink" variant="outline">
            <Phone class="h-4 w-4" />
            Anrufen
        </Button>
        <Button as="a" :href="mailLink" variant="outline">
            <Mail class="h-4 w-4" />
            E-Mail verfassen
        </Button>
        <Button v-if="showUnassignButton" variant="outline" @click="unassignAdvice" title="Beratung freigeben">
            <Unlock class="h-4 w-4" />
            Beratung freigeben
        </Button>
        <AdviceTransfer :advice-id="advice.id" :transferable-groups="transferableGroups" />
    </div>
</template>
