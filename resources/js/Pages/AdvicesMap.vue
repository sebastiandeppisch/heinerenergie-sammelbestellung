<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import { computed, onMounted, reactive, ref, watch } from 'vue';

import { LControl, LControlLayers, LIcon, LLayerGroup, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';

//import 'leaflet/dist/leaflet.css';
import { Button } from '@/shadcn/components/ui/button';
import { router, usePage } from '@inertiajs/vue3';
import { LPolygon } from '@vue-leaflet/vue-leaflet';
import axios from 'axios';
import { DxTextBox, DxButton as DxTextBoxButton } from 'devextreme-vue/text-box';
import notify from 'devextreme/ui/notify';
import { latLng } from 'leaflet';
import { ExternalLink, UserCheck } from 'lucide-vue-next';
import AdviceTypes from '../AdviceTypes';
import { isAdmin, user as userRef } from '../authHelper';
import { AdaptTableHeight } from '../helpers';

const user = userRef.value;
const userId = user.id;

const emit = defineEmits(['selectAdviceId']);

const props = defineProps<{
    advices: App.Data.DataProtectedAdviceData[];
    advisors: App.Data.UserData[];
    groups: App.Data.GroupMapData[];
}>();
const advisors = props.advisors;

const advices = computed(() => {
    return props.advices;
});

const map = reactive({
    center: latLng(49.8728, 8.6512) as { lat: number; lng: number },
    zoom: 15,
});

const page = usePage();
const hash = window.location.hash;

if (hash !== '') {
    const parts = hash.replace('#', '').split('/');
    map.zoom = parseInt(parts[0]);
    map.center.lat = parseFloat(parts[1]);
    map.center.lng = parseFloat(parts[2]);
} else if (user.lat !== null && user.long !== null && user.lat !== undefined && user.long !== undefined) {
    map.center = latLng(user.lat, user.long);
}

function advisorName(advisorId: string | null) {
    const advisor = advisors.find((advisor) => advisor.id === advisorId);
    if (advisor === undefined) {
        return '';
    }
    return advisor.name;
}

function openAdvice(advice: App.Data.DataProtectedAdviceData) {
    router.get('/advices/' + advice.id);
}

function ownId() {
    return user.id;
}

function addAdvice(advice: App.Data.DataProtectedAdviceData) {
    axios
        .post('api/advices/' + advice.id + '/assign')
        .then((response) => response.data)
        .then((advice) => {
            router.visit('/advicesmap' + '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng);
        });
}

function userCanOpen(advice: App.Data.DataProtectedAdviceData) {
    if (isAdmin) {
        return true;
    }
    if (advice.advisor_id === userId) {
        return true;
    }
    if (advice.shares_ids.includes(userId)) {
        return true;
    }
    return false;
}

function zoomChanged(e: number) {
    map.zoom = e;
}

function centerChanged(e: { lat?: number; lng?: number }) {
    if ('lat' in e === false || 'lng' in e === false) {
        return;
    }

    if (e.lat === undefined || e.lng === undefined) {
        return;
    }

    map.center.lat = e.lat;
    map.center.lng = e.lng;
}

const outer = ref(null);
const tableHeight = new AdaptTableHeight(outer);
const reactiveHeight = tableHeight.getReactive();

const search = ref('');
onMounted(() => {
    tableHeight.calcHeight();
});

watch(map, () => {
    window.location.hash = '#' + map.zoom + '/' + map.center.lat + '/' + map.center.lng;
});

function runSearch() {
    axios
        .get('api/map/search', { params: { query: search.value } })
        .then((response) => response.data)
        .then((data: App.ValueObjects.Coordinate) => {
            if (data['lat'] === undefined || data['lng'] === undefined) {
                notify('Adresse nicht gefunden', 'error');
                return;
            }
            map.center = latLng(data.lat, data.lng);
            map.zoom = 18; //a better approach would be to set the bounding box
        });
}
</script>

<template>
    <div ref="outer">
        <div :style="{ height: reactiveHeight.height + 170 + 'px', width: '100%' }">
            <LMap
                :zoom="map.zoom"
                @update:zoom="zoomChanged"
                :center="[map.center.lat, map.center.lng]"
                @update:center="centerChanged"
                :minZoom="3"
                :maxZoom="18"
            >
                <LControlLayers :collapsed="false" :hide-single-base="true" />
                <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap" />
                <LControl position="topleft">
                    <div class="dx-card">
                        <div style="display: flex">
                            <DxTextBox placeholder="Springe zu Adresse" v-model="search" value-change-event="keyup" @enterKey="runSearch">
                                <DxTextBoxButton
                                    :options="{
                                        icon: 'search',
                                        onClick: runSearch,
                                    }"
                                    name="search"
                                    location="after"
                                />
                            </DxTextBox>
                        </div>
                    </div>
                </LControl>
                <LControl position="bottomleft">
                    <div class="dx-card" style="width: fit-content">
                        <div style="display: flex; flex-direction: row">
                            <div style="display: flex; flex-direction: column; margin: 10px">
                                <div><b>Freie Beratungen</b></div>
                                <div style="display: flex; flex-direction: row; justify-content: center; gap: 15px; padding-top: 5px">
                                    <img src="/images/markers/house_magenta.svg" style="height: 3em; display: inline" />
                                    <img src="/images/markers/phone_magenta.svg" style="height: 3em; display: inline" />
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; margin: 10px">
                                <div><b>Deine Beratungen</b></div>
                                <div style="display: flex; flex-direction: row; justify-content: center; gap: 15px; padding-top: 5px">
                                    <img src="/images/markers/house_green.svg" style="height: 3em; display: inline" />
                                    <img src="/images/markers/phone_green.svg" style="height: 3em; display: inline" />
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; margin: 10px">
                                <div><b>Vergebene Beratungen</b></div>
                                <div style="display: flex; flex-direction: row; justify-content: center; gap: 15px; padding-top: 5px">
                                    <img src="/images/markers/house_blue.svg" style="height: 3em; display: inline" />
                                    <img src="/images/markers/phone_blue.svg" style="height: 3em; display: inline" />
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; margin: 10px">
                                <div><b>Berater*innen</b></div>
                                <div style="display: flex; flex-direction: row; justify-content: center; gap: 15px; padding-top: 5px">
                                    <img src="/images/markers/he_yellow.svg" style="height: 3em; display: inline" />
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; margin: 10px">
                                <div><b>Erledigte Beratungen</b></div>
                                <div style="display: flex; flex-direction: row; justify-content: center; gap: 15px; padding-top: 5px">
                                    <img src="/images/markers/gray_green.svg" style="height: 3em; display: inline" />
                                    <img src="/images/markers/gray_red.svg" style="height: 3em; display: inline" />
                                </div>
                            </div>
                        </div>
                    </div>
                </LControl>

                <LLayerGroup name="Beratungen" layer-type="overlay">
                    <LMarker
                        v-for="advice in advices.filter((advice) => advice.result < 2).filter((advice) => advice.lat !== null && advice.lng !== null)"
                        :key="advice.id"
                        :lat-lng="latLng(advice.lat ?? 0, advice.lng ?? 0)"
                    >
                        <LIcon
                            v-if="advice.advisor_id === null && advice.type === AdviceTypes.Home"
                            icon-url="/images/markers/house_magenta.svg"
                            :icon-size="[50, 50]"
                        />
                        <LIcon
                            v-else-if="advice.advisor_id === null && advice.type === AdviceTypes.Virtual"
                            icon-url="/images/markers/phone_magenta.svg"
                            :icon-size="[50, 50]"
                        />
                        <LIcon
                            v-else-if="advice.advisor_id === ownId() && advice.type === AdviceTypes.Home"
                            icon-url="/images/markers/house_green.svg"
                            :icon-size="[50, 50]"
                        />
                        <LIcon
                            v-else-if="advice.advisor_id === ownId() && advice.type === AdviceTypes.Virtual"
                            icon-url="/images/markers/phone_green.svg"
                            :icon-size="[50, 50]"
                        />
                        <LIcon
                            v-else-if="advice.advisor_id !== ownId() && advice.type === AdviceTypes.Home"
                            icon-url="/images/markers/house_blue.svg"
                            :icon-size="[50, 50]"
                        />
                        <LIcon
                            v-else-if="advice.advisor_id !== ownId() && advice.type === AdviceTypes.Virtual"
                            icon-url="/images/markers/phone_blue.svg"
                            :icon-size="[50, 50]"
                        />
                        <LPopup>
                            <div>
                                <b
                                    >{{ advice.first_name }} {{ advice.last_name }}<br />
                                    {{ advice.street }} {{ advice.street_number }}<br />
                                </b>
                                <div v-if="advice.advisor_id !== null">Berater*in: {{ advisorName(advice.advisor_id) }}</div>
                                <div v-if="advice.shares_ids.length > 0">Geteilt mit: {{ advice.shares_ids.map(advisorName).join(', ') }}</div>
                                <Button v-if="advice.advisor_id === null" variant="default" @click="addAdvice(advice)" class="mt-2 w-full">
                                    <UserCheck class="h-4 w-4" />
                                    Beratung übernehmen
                                </Button>
                                <Button variant="outline" @click="openAdvice(advice)" class="mt-2 w-full" v-if="advice.can_edit">
                                    <ExternalLink class="h-4 w-4" />
                                    Beratung öffnen
                                </Button>
                            </div>
                        </LPopup>
                    </LMarker>
                </LLayerGroup>
                <LLayerGroup name="Erledigte Beratungen" layer-type="overlay" :visible="false">
                    <LMarker
                        v-for="advice in advices
                            .filter((advice) => advice.result >= 2)
                            .filter((advice) => advice.lat !== null && advice.lng !== null)"
                        :key="advice.id"
                        :lat-lng="latLng(advice.lat ?? 0, advice.lng ?? 0)"
                    >
                        <LIcon v-if="advice.result === 2" icon-url="/images/markers/gray_green.svg" :icon-size="[50, 50]" />
                        <LIcon v-else-if="advice.result === 3" icon-url="/images/markers/gray_red.svg" :icon-size="[50, 50]" />
                        <LPopup>
                            <div>
                                <b
                                    >{{ advice.first_name }} {{ advice.last_name }}<br />
                                    {{ advice.street }} {{ advice.street_number }}<br />
                                </b>
                                <div v-if="advice.advisor_id !== null">Berater*in: {{ advisorName(advice.advisor_id) }}</div>
                                <div v-if="advice.shares_ids.length > 0">Geteilt mit: {{ advice.shares_ids.map(advisorName).join(', ') }}</div>
                                <Button variant="outline" @click="openAdvice(advice)" class="mt-2 w-full" v-if="userCanOpen(advice)">
                                    <ExternalLink class="h-4 w-4" />
                                    Beratung öffnen
                                </Button>
                            </div>
                        </LPopup>
                    </LMarker>
                </LLayerGroup>
                <LLayerGroup name="Berater*innen" layer-type="overlay">
                    <LMarker
                        v-for="advisor in advisors.filter((advice) => advice.lat !== null && advice.long !== null)"
                        :key="advisor.id"
                        :lat-lng="latLng(advisor.lat ?? 0, advisor.long ?? 0)"
                    >
                        <LIcon icon-url="/images/markers/he_yellow.svg" :icon-size="[50, 50]" />
                        <LPopup>
                            <div>
                                {{ advisor.name }}
                            </div>
                        </LPopup>
                    </LMarker>
                </LLayerGroup>

                <LLayerGroup name="Initiativen" layer-type="overlay">
                    <!-- eslint-disable vue/no-use-v-if-with-v-for -->
                    <LPolygon
                        v-if="props.groups && props.groups.length > 0"
                        v-for="(item, index) in props.groups"
                        :key="index"
                        :lat-lngs="item.polygon.coordinates"
                    />
                    <LMarker
                        v-for="(item, index) in props.groups"
                        :key="index"
                        :lat-lng="latLng(item.center.lat, item.center.lng)"
                        v-if="props.groups && props.groups.length > 0"
                    >
                        <LIcon :icon-url="item.logo_path" :icon-size="[50, 50]" v-if="item.logo_path !== null" />
                    </LMarker>
                    <!-- eslint-enable -->
                </LLayerGroup>
            </LMap>
        </div>
    </div>
</template>
