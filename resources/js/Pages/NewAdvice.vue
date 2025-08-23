<script setup lang="ts">
import { DxItem, DxMultiView } from 'devextreme-vue/multi-view';
import Contact from '../components/AdviceFormSlides/Contact.vue';
import HelpType from '../components/AdviceFormSlides/HelpType.vue';
import HouseType from '../components/AdviceFormSlides/HouseType.vue';
import Place from '../components/AdviceFormSlides/Place.vue';
import Sent from '../components/AdviceFormSlides/Sent.vue';
import SlideCard from '../components/AdviceFormSlides/SlideCard.vue';
import Submit from '../components/AdviceFormSlides/Submit.vue';

import { reactive, ref } from 'vue';
import PublicLayout from '../layouts/PublicLayout.vue';

type BaseAdvice = Pick<
    App.Models.Advice,
    | 'commentary'
    | 'help_type_place'
    | 'help_type_technical'
    | 'help_type_bureaucracy'
    | 'help_type_other'
    | 'house_type'
    | 'first_name'
    | 'last_name'
    | 'email'
    | 'phone'
    | 'zip'
    | 'city'
    | 'street'
    | 'street_number'
    | 'place_notes'
    | 'landlord_exists'
    | 'type'
>;

type NewAdvice = Omit<BaseAdvice, 'type' | 'zip'> & {
    type: number | null;
    zip: number | null;
};

defineOptions({
    layout: PublicLayout,
});

const props = defineProps<{
    groupId: string;
}>();

const r = reactive({
    advice: {
        commentary: null,
        help_type_place: false,
        help_type_technical: false,
        help_type_bureaucracy: false,
        help_type_other: false,
        house_type: null,
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        zip: null,
        city: '',
        street: '',
        street_number: '',
        place_notes: '',
        landlord_exists: null,
        type: null,
        group_id: props.groupId,
    } as NewAdvice,
    selectedSlide: 0,
});

const multiView = ref(null);

function forward() {
    r.selectedSlide++;
    if (isAdditionalSlide()) {
        forward();
    }
}

function isAdditionalSlide() {
    return r.advice.type === 2 && (r.selectedSlide === 2 || r.selectedSlide === 3);
}

function backward() {
    r.selectedSlide--;
    if (isAdditionalSlide()) {
        backward();
    }
}

function submit() {
    r.selectedSlide++;
}
</script>

<style scoped>
.breadcrumb-active {
    font-weight: bold;
}

.breadcrumb-not-reached {
    opacity: 0.5;
}
</style>

<template>
    <div style="max-width: 600px">
        <div
            style="
                margin-left: 20px;
                margin-right: 20px;
                padding: 8px;
                display: flex;
                justify-content: space-between;
                padding-right: 32px;
                padding-left: 32px;
                user-select: none;
            "
            class="dx-card"
        >
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 0, 'breadcrumb-active': r.selectedSlide === 0 }">Kontakt</span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 1 }">></span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 1, 'breadcrumb-active': r.selectedSlide === 1 }">Adresse</span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 2 }">></span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 2 || r.advice.type === 2, 'breadcrumb-active': r.selectedSlide === 2 }"
                >Beratung</span
            >
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 3 }">></span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 3 || r.advice.type === 2, 'breadcrumb-active': r.selectedSlide === 3 }"
                >Gebäudeart</span
            >
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 4 }">></span>
            <span :class="{ 'breadcrumb-not-reached': r.selectedSlide < 4, 'breadcrumb-active': r.selectedSlide > 3 }">Absenden</span>
        </div>
        <DxMultiView height="440px" :swipe-enabled="false" ref="multiView" :selected-index="r.selectedSlide">
            <DxItem>
                <SlideCard @forward="forward" @backward="backward" v-slot="scope" :show-backward="false">
                    <Contact v-model="r.advice" @allow-forward="scope.allowForward" />
                </SlideCard>
            </DxItem>
            <DxItem>
                <SlideCard @forward="forward" @backward="backward" v-slot="scope">
                    <Place v-model="r.advice" @allow-forward="scope.allowForward" />
                </SlideCard>
            </DxItem>
            <DxItem>
                <SlideCard @forward="forward" @backward="backward" v-slot="scope">
                    <HelpType v-model="r.advice" @allow-forward="scope.allowForward" />
                </SlideCard>
            </DxItem>
            <DxItem>
                <SlideCard @forward="forward" @backward="backward" v-slot="scope">
                    <HouseType v-model="r.advice" @allow-forward="scope.allowForward" />
                </SlideCard>
            </DxItem>
            <DxItem>
                <SlideCard @forward="forward" @backward="backward" v-slot="scope" :show-forward="false">
                    <Submit v-model="r.advice" @allow-forward="scope.allowForward" @submit="submit" />
                </SlideCard>
            </DxItem>
            <DxItem>
                <SlideCard @forward="forward" :show-backward="false" :show-forward="false">
                    <Sent v-model="r.advice" />
                </SlideCard>
            </DxItem>
        </DxMultiView>
    </div>
</template>
<style scoped></style>
